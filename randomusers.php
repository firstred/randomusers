<?php
/**
 * 2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Randomusers
 */
class RandomUsers extends Module
{
    const GENERATE_USERS = 'RANDOMUSERS_GENERATE_USERS';

    /**
     * Randomusers constructor.
     */
    public function __construct()
    {
        $this->name = 'randomusers';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Michael Dekker';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Random users');
        $this->description = $this->l('Generate random users');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Install the module
     *
     * @return bool Whether the module has been successfully installed
     */
    public function install()
    {
        return parent::install();
    }

    /**
     * Uninstall the module
     *
     * @return bool Whether the module has been successfully uninstalled
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Get the module's configuration page
     *
     * @return string Configuration page HTML
     * @throws Exception
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';

        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitRandomusersModule')) == true) {
            $output .= $this->postProcess();
        }

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRandomusersModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Generate users'),
                'icon' => 'icon-users',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'desc' => $this->l('How many users do you want to generate?').'<br />'.$this->l('This module does not guarantee the number of users added. It depends on the amount of email address collissions.'),
                        'name' => self::GENERATE_USERS,
                        'label' => $this->l('Generate users'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            self::GENERATE_USERS => 10,
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit(self::GENERATE_USERS)) {
            $total = (int) Tools::getValue(self::GENERATE_USERS);

            $max = ceil($total / 1000);

            for ($i = 0; $i < $max; $i++) {
                if ($total - 1000 > 0) {
                    $total -= 1000;
                    $chunk = 1000;
                } else {
                    $chunk = $total;
                }
                $users = Tools::file_get_contents('https://randomuser.me/api/?results='.(int) $chunk);

                if (!$users = Tools::jsonDecode($users, true)) {
                    return $this->displayError($this->l('Could not read from Randomuser.me API'));
                }
                $users = $users['results'];

                foreach ($users as $user) {
                    $customer = new Customer();
                    if (!Validate::isEmail($user['email'])) {
                        continue;
                    }
                    $customer->email = $user['email'];
                    $customer->firstname = ucfirst($user['name']['first']);
                    $customer->lastname = ucfirst($user['name']['last']);
                    $customer->passwd = $user['login']['md5'];
                    $customer->save();
                }
            }


            return $this->displayConfirmation($this->l('Successfully generated customers'));
        }

        return '';
    }
}
