<?php

/**
 * "PayPal Emails Banlist" Module for WHMCS
 *
 * This addon baning PayPal emails, so that they can't make purchases.
 *
 * @author Ruslan Ivanov
 */

include_once(dirname(__FILE__) . '/guardianAPI/GuardianApiCaller.php');

/**
 * @return array
 */
function paypal_emails_banlist_config()
{
    $configarray = [
        "name"        => "PayPal Emails Banlist",
        "description" => "This addon baning PayPal emails, so that they can't make purchases",
        "version"     => "1.0 <sup style='color: #46a546'>stable</sup>",
        "author"      => "Ruslan Ivanov",
        "fields"      => [
            "messagename" => [
                "FriendlyName" => "Email template",
                "Type"         => "text",
                "Description"  => "The name of the client email template to send",
            ],
            "sandbox" => [
                "FriendlyName" => "Enable sandbox mode?",
                "Type"         => "yesno",
                "Description"  => "If now you using sandbox PayPal mode in you WHMCS please tick it",
            ],
        ],
    ];

    return $configarray;
}

/**
 * Activate module
 */
function paypal_emails_banlist_activate()
{
    # Return Result
    return [
        'status'      => 'success',
        'description' => 'Module activated. Configure it.',
    ];
}

/**
 * Deactivate module
 */
function paypal_emails_banlist_deactivate()
{
    # Return Result
    return [
        'status'      => 'success',
        'description' => 'Module deactivated',
    ];
}

/**
 * @param array $vars
 * @return bool
 */
function paypal_emails_banlist_output($vars)
{
    $caller = new GuardianApiCaller();

    try {
        // POST requests
        if (isset($_POST['action']) and strlen($_POST['action']) > 0) {
            switch ($_POST['action']) {
                case 'banemail':
                    // add email to ban list
                    banEmail($caller);
                    break;
                case 'searchemail':
                    // search email in ban list
                    if(searchEmail($caller, $vars)) {
                        return true;
                    }
                    break;
                case 'deletemail':
                    // delete email of banlist
                    deleteEmail($caller);
                    break;
            }
        }

        // get emails list
        if (!empty($_GET['page']) and is_int((int)$_GET['page'])) {
            // get pagination
            $result = $caller->getBannedEmails($_GET['page']);
        } else if (!empty($_POST['page']) and $_POST['page'] > 0) {
            // post pagination
            $result = $caller->getBannedEmails($_POST['page']);
        } else {
            // index page of module
            $result = $caller->getBannedEmails();
        }

        $result = json_decode($result);
        includeTemplate($result->result, $vars);

    } catch (Exception $e) {
        echo '<div class="errorbox">
                <strong>
                  <span class="title">An error has occurred while working module!</span>
                </strong>
                <br>Exception :' . $e->getMessage() . '
              </div>';
    }
}

/**
 * Add email to banlist
 *
 * @param object $caller
 */
function banEmail($caller)
{
    $email = $_POST['email'];
    if(!empty($_POST['description'])) {
        $description = $_POST['description'];
    } else {
        $description = false;
    }


    $result = $caller->addEmailInBanList($email, $description);
    $result = json_decode($result);

    if ($result->status == 'success') {
        echo '<div class="successbox">
                <strong>
                  <span class="title">Email banned!</span>
                </strong>
                <br>Thank you for using ban list.
              </div>';
    } else {
        $str = '';

        foreach ($result->data as $value) {
            $str .= '<li>' . $value[0] . '</li>';
        }

        echo '<div class="errorbox">
                <strong><span class="title">Error while adding email to banlist</span></strong>
                <br>
                <ul>' . $str . '</ul>
              </div>';
    }
}

/**
 * Search email in banlist
 *
 * @param object $caller
 * @param array $vars
 */
function searchEmail($caller, $vars)
{
    if(!empty($_POST['email'])) {
        $result = $caller->searchEmailInBanList($_POST['email']);
        $result = json_decode($result);

        $old['search_email'] = $_POST['email'];
        if ($result->status == 'success') {
            if(!$result->result) {
                echo '<div class="infobox">
                    <strong>
                      <span class="title">Email not found in banlist!</span>
                    </strong>
                  </div>';
            }
            includeTemplate($result->result, $vars, $old);
            return true;
        }
    } else {
        echo '<div class="errorbox">
                <strong><span class="title">Validate error</span></strong>
                <br>
                Email must be longer than three characters
              </div>';
    }
}

/**
 * Delete email of banlist
 *
 * @param object $caller
 */
function deleteEmail($caller)
{
    if(isset($_POST['delete_id']) and strlen($_POST['delete_id']) > 0 and is_int((int)$_POST['delete_id'])) {
        $result = $caller->deleteEmailOfBanList($_POST['delete_id']);
        $result = json_decode($result);

        if ($result->status == 'success') {
            echo '<div class="successbox">
                <strong>
                  <span class="title">Email deleted of banlist!</span>
                </strong>
                <br>Thank you for using ban list.
              </div>';
        }
    }
}

/**
 * Include admin area module template
 *
 * @param array $data
 * @param mixed $vars
 * @param mixed $old
 */
function includeTemplate($data, $vars = false, $old = false)
{
    include_once(dirname(__FILE__) . '/templates/tpl_paypal_emails_banlist_config.php');
}