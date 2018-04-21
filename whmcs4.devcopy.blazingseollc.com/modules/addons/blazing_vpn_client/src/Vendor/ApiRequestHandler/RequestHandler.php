<?php

namespace Blazing\Vpn\Client\Vendor\ApiRequestHandler;

use Blazing\Vpn\Client\Vendor\ApiRequestHandler\Exception\BadRequestException;
use Blazing\Vpn\Client\Vendor\ApiRequestHandler\Exception\RequestsException;
use Blazing\Vpn\Client\Vendor\Buzz\Browser;
use Blazing\Vpn\Client\Vendor\Buzz\Exception\RequestException;
use Blazing\Vpn\Client\Vendor\Buzz\Message\Response;
use ErrorException;

class RequestHandler
{

    /**
     * @var Configuration
     */
    protected $configuration;

    public function __construct(ApiConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function get($path, array $data = [], array $headers = [])
    {
        return $this->request($path, $data, 'GET', $headers);
    }

    public function put($path, array $data = [], array $headers = [])
    {
        return $this->request($path, $data, 'PUT', $headers);
    }

    public function post($path, array $data = [], array $headers = [])
    {
        return $this->request($path, $data, 'POST', $headers);
    }

    public function delete($path, array $data = [], array $headers = [])
    {
        return $this->request($path, $data, 'DELETE', $headers);
    }

    public function request($path, array $data = [], $method = 'POST', array $headers = [])
    {
        // Replace path variables
        foreach ($data as $key => $value) {
            if ($value and false !== strpos($path, '{' . $key . '}')) {
                $path = str_replace('{' . $key . '}', (string) $value, $path);
                unset($data[$key]);
            }
        }

        // Determine URI
        $url = sprintf('%s://%s/%s/%s',
            $this->configuration->getProtocol(),
            $this->configuration->getHost(),
            trim($this->configuration->getUrl(), '/'),
            trim($path, '/'));

        $browser = new Browser();
        $browser->getClient()->setTimeout($this->configuration->getRequestOption('timeout'));
        $browser->getClient()->setVerifyHost($this->configuration->getRequestOption('verifyHost'));
        $browser->getClient()->setVerifyPeer($this->configuration->getRequestOption('verifyPeer'));
        $browser->getClient()->setMaxRedirects($this->configuration->getRequestOption('maxRedirects'));
        $tries = $this->configuration->getRequestOption('retry', 3);

        try {
            while (true) {
                try {
                    /** @var Response $response */
                    $response = $browser->submit($url, $data, $method, $headers);
                    $content = $response->getContent();

                    if (200 !== $response->getStatusCode() and '{' != substr($content, 0, 1)) {
                        throw new RequestException(sprintf('Error %s: %s', $response->getStatusCode(), substr($content, 0, 250)));
                    }

                    break;
                }
                catch (RequestException $e) {
                    $tries--;

                    // Stop trying
                    if ($tries <= 0) {
                        // Rethrow an exception
                        throw $e;
                    }

                    if ($this->configuration->getLogger()) {
                        $this->configuration->getLogger()->warn("API: Request exception $method:$path", [
                            'error' => $e->getMessage(),
                            'triesLeft' => $tries,
                            'response' => !empty($response) ? substr($response->getContent(), 0, 250) : null
                        ]);
                    }

                    sleep(1);
                }
            }

            if (empty($content)) {
                throw new ErrorException('Empty content in response');
            }

            $response = @json_decode($content, true);
            if (!$response) {
                throw new ErrorException('No data in response, content: ' . $content);
            }

            if (!empty($response['status']) and 'error' == $response['status']) {
                throw new BadRequestException(
                    !empty($response['message']) ? $response['message'] : $content,
                    $response,
                    $content
                );
            }
        }
        catch (BadRequestException $e) {
            if ($this->configuration->getLogger()) {
                $this->configuration->getLogger()->warn("API: Request exception $method:$path", [
                    'error' => $e->getMessage(),
                    'data' => $e->getData(),
                    'url'  => $url,
                    'requestData' => $data,
                ]);
            }

            throw $e;
        }
        catch (\Exception $e) {
            if ($this->configuration->getLogger()) {
                $this->configuration->getLogger()->warn("API: Request error $method:$path", [
                    'error' => $e->getMessage(),
                    'url'   => $url,
                    'requestData' => $data,
                ]);
            }
            throw new RequestsException("Request error ($method:$path): " . $e->getMessage());
        }

        if ($this->configuration->getLogger()) {
            $logData = $response;

            // Cut large data
            if (!empty($logData[ 'list' ]) and 10 < count($logData[ 'list' ])) {
                $logData[ 'list' ] = array_merge(array_slice(array_values($logData[ 'list' ]), 0, 10), ['large data...']);
            }

            $this->configuration->getLogger()->debug("API: Response $method:$path", [
                'parameters' => $data,
                'response'   => $logData,
                'url'        => $url
            ]);
        }

        return $response;
    }
}
