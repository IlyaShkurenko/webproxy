<?php

namespace Vendor;

class ParallelCurl {

    private $max_concurrent_requests;
    private $max_requests_per_second;
    private $options;

    private $outstanding_requests;
    private $multi_handle;

    private $request_count;
    private $request_count_time;

    public function __construct($in_max_concurrent_requests = 10, $in_max_requests_per_second = 0, $in_options = array()) {
        $this->max_requests = $in_max_concurrent_requests;
        $this->max_requests_per_second = $in_max_requests_per_second;

        $this->request_count = 0;
        $this->request_count_time = microtime(true);

        $this->options = $in_options;

        $this->outstanding_requests = array();
        $this->multi_handle = curl_multi_init();
    }

    //Ensure all the requests finish nicely
    public function __destruct() {
        $this->finishAllRequests();
    }

    // Sets how many requests can be outstanding at once before we block and wait for one to
    // finish before starting the next one
    public function setMaxConcurrentRequests($in_max_concurrent_requests) {
        $this->max_concurrent_requests = $in_max_concurrent_requests;
    }

    // Sets how many requests can be sent within one second time
    public function setMaxRequestsPerSecond($in_max_requests_per_second) {
        $this->max_requests_per_second = $in_max_requests_per_second;
    }

    // Sets the options to pass to curl, using the format of curl_setopt_array()
    public function setOptions($in_options) {
        $this->options = $in_options;
    }

    // Start a fetch from the $url address, calling the $callback function passing the optional
    // $user_data value. The callback should accept 3 arguments, the url, curl handle and user
    // data, eg on_request_done($url, $ch, $user_data);
    public function startRequest($url, $callback, $user_data = array(), $proxy = null) {
        if( $this->max_concurrent_requests > 0 ) {
            $this->waitForOutstandingRequestsToDropBelow($this->max_concurrent_requests);
        }
        if( $this->max_requests_per_second > 0 ) {
            $this->waitForRequestCountTimeToDropBelow($this->max_requests_per_second);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $this->options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if (isset($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        curl_multi_add_handle($this->multi_handle, $ch);

        $ch_int = (int) $ch;
        $this->outstanding_requests[$ch_int] = array(
            'url' => $url,
            'callback' => $callback,
            'user_data' => $user_data,
        );

        $this->checkForCompletedRequests();
    }

    // You *MUST* call this function at the end of your script. It waits for any running requests
    // to complete, and calls their callback functions
    public function finishAllRequests() {
        $this->waitForOutstandingRequestsToDropBelow(1);
    }

    // Checks to see if any of the outstanding requests have finished
    private function checkForCompletedRequests() {
        // Call select to see if anything is waiting for us
        curl_multi_select($this->multi_handle, 1.0);

        // Since something's waiting, give curl a chance to process it
        do {
            $mrc = curl_multi_exec($this->multi_handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        // Now grab the information about the completed requests
        while ($info = curl_multi_info_read($this->multi_handle)) {
            $ch = $info['handle'];

            $ch_int = (int) $ch;

            if (!isset($this->outstanding_requests[$ch_int])) {
                die("Error - handle wasn't found in requests: '$ch' in ".
                    print_r($this->outstanding_requests, true));
            }

            $request = $this->outstanding_requests[$ch_int];

            $url = $request['url'];
            $content = curl_multi_getcontent($ch);
            $callback = $request['callback'];
            $user_data = $request['user_data'];

            call_user_func($callback, $content, $url, $ch, $user_data);

            unset($this->outstanding_requests[$ch_int]);

            curl_multi_remove_handle($this->multi_handle, $ch);
        }
    }

    // Blocks until there's less than the specified number of requests outstanding
    private function waitForOutstandingRequestsToDropBelow($max) {
        while (1) {
            $this->checkForCompletedRequests();
            if (count($this->outstanding_requests)<$max) {
                break;
            }
            usleep(10000);
        }
    }

    // Blocks until there is sufficient time between requests
    private function waitForRequestCountTimeToDropBelow($max) {
        while (1) {
            if((microtime(true) - $this->request_count_time) >= 1) {
                $this->request_count_time = microtime(true);
                $this->request_count = 0;
            }
            if ($this->request_count < $max) {
                $this->request_count++;
                break;
            }
            usleep(10000);
        }
    }

}