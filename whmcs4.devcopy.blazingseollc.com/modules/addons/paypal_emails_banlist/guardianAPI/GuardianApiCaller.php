<?php

/**
 * Class GuardianApiCaller
 *
 * Worked with guardian service API
 *
 * @author Ruslan Ivanov
 */

class GuardianApiCaller
{
    /**
     * Guardian API URL
     * @var string
     */
    protected $apiURL = 'http://66.154.116.67/api/';

    /**
     * Delete email of the ban list
     *
     * @param $id
     * @return string
     */
    public function deleteEmailOfBanList($id)
    {

        // send request to guardian service api
        $result = $this->callAPI('delete_of_ban/' . $id);

        return $result;
    }

    /**
     * Add email to ban list
     *
     * @param string $email
     * @param string $description
     * @return string
     */
    public function addEmailInBanList($email, $description = false)
    {
        $data = [
            'user_email' => $email,
            'description' => $description,
        ];

        // send request to guardian service api
        $result = $this->callAPI('add_to_ban', $data);

        return $result;
    }

    /**
     * Check exist email in ban list
     *
     * @param string $email
     * @return string
     */
    public function checkEmailInBanList($email)
    {
        $data = [
            'user_email' => $email,
        ];

        // send request to guardian service api
        $result = $this->callAPI('check_in_ban', $data);

        return $result;
    }

    /**
     * Search email in the ban list
     *
     * @param string $email
     * @return string
     */
    public function searchEmailInBanlist($email)
    {
        $data = [
            'user_email' => $email,
        ];

        // send request to guardian service api
        $result = $this->callAPI('search_in_ban', $data);

        return $result;
    }

    /**
     * Get banned emails list
     *
     * @param mixed $page
     * @return string
     */
    public function getBannedEmails($page = false)
    {
        $url = 'banned_emails';

        if(!empty($page)) {
            $url = $url . '?page=' . $page;
        }

        // send request to guardian service api
        $result = $this->callAPI($url);

        return $result;
    }

    /**
     * Send request to url with data
     *
     * @param string $url
     * @param array $data
     * @return mixed
     */
    protected function callAPI($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiURL . $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            http_build_query($data)
        );

        return curl_exec($ch);
    }
}
