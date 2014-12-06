<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCI\Command;

use PHPCI\Helper\Lang;
use PHPCI\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use b8\Store\Factory;
use PHPCI\Builder;

/**
* Create admin command - creates an admin user
* @author       Wogan May (@woganmay)
* @package      PHPCI
* @subpackage   Console
*/
class CreateAdminCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('phpci:create-admin')
            ->setDescription(Lang::get('create_admin_user'));
    }

    /**
    * Creates an admin user in the existing PHPCI database
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userStore = Factory::getStore('User');
        $userService = new UserService($userStore);

        require(PHPCI_DIR . 'bootstrap.php');

        // Try to create a user account:
        $adminEmail = $this->ask(Lang::get('enter_email'), true, FILTER_VALIDATE_EMAIL);

        if (empty($adminEmail)) {
            return;
        }

        $adminPass = $this->ask(Lang::get('enter_pass'));
        $adminName = $this->ask(Lang::get('enter_name'));

        try {
            $userService->createUser($adminName, $adminEmail, $adminPass, 1);
            print Lang::get('user_created') . PHP_EOL;
        } catch (\Exception $ex) {
            print Lang::get('failed_to_create') . PHP_EOL;
            print $ex->getMessage();
            print PHP_EOL;
        }
    }

    protected function ask($question, $emptyOk = false, $validationFilter = null)
    {
        print $question . ' ';

        $rtn    = '';
        $stdin     = fopen('php://stdin', 'r');
        $rtn = fgets($stdin);
        fclose($stdin);

        $rtn = trim($rtn);

        if (!$emptyOk && empty($rtn)) {
            $rtn = $this->ask($question, $emptyOk, $validationFilter);
        } elseif ($validationFilter != null  && ! empty($rtn)) {
            if (! $this -> controlFormat($rtn, $validationFilter, $statusMessage)) {
                print $statusMessage;
                $rtn = $this->ask($question, $emptyOk, $validationFilter);
            }
        }

        return $rtn;
    }
    protected function controlFormat($valueToInspect, $filter, &$statusMessage)
    {
        $filters = !(is_array($filter))? array($filter) : $filter;
        $statusMessage = '';
        $status = true;
        $options = array();

        foreach ($filters as $filter) {
            if (! is_int($filter)) {
                $regexp = $filter;
                $filter = FILTER_VALIDATE_REGEXP;
                $options = array(
                    'options' => array(
                        'regexp' => $regexp,
                    )
                );
            }
            if (! filter_var($valueToInspect, $filter, $options)) {
                $status = false;

                switch ($filter)
                {
                    case FILTER_VALIDATE_URL:
                        $statusMessage = Lang::get('must_be_valid_url') . PHP_EOL;
                        break;
                    case FILTER_VALIDATE_EMAIL:
                        $statusMessage = Lang::get('must_be_valid_email') . PHP_EOL;
                        break;
                    case FILTER_VALIDATE_REGEXP:
                        $statusMessage = Lang::get('incorrect_format') . PHP_EOL;
                        break;
                }
            }
        }

        return $status;
    }
}
