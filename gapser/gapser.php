<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Gapser extends Module
{
    protected $config_form = false;
    public function __construct()
    {
        $this->name = 'gapser';
        $this->tab = 'content_management';
        $this->version = '1.0.2';
        $this->author = 'Krzysztof Towalski';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        header('Content-Type: text/html; charset=utf-8');
        $this->displayName = $this->l('GaPSer - Moduł Zgodności Dyrektywy GPSR');
        $this->description = $this->l('Ten moduł pomoże Twojemu sklepowi zachować zgodność z dyrektywą UE GPSR, która wymaga od Ciebie informowania klientów o polityce bezpieczeństwa producenta produktu.');
        $this->confirmUninstall = $this->l('Na pewno chcesz wywalić?');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {       
            Configuration::updateValue('GAPSER_DOSTAWCA_WLACZ', false, false);
            Configuration::updateValue('GAPSER_PRODUCENT_WLACZ', true, false);
            Configuration::updateValue('GAPSER_WYLACZ_STARE', true, false);          
            Configuration::updateValue('GAPSER_NAZWA_ZAKLADKI_PRODUCENTA', 'Producent', false);
            Configuration::updateValue('GAPSER_NAZWA_ZAKLADKI_DOSTAWCY', 'Dostawca', false);
            Configuration::updateValue('GAPSER_WLACZ_INFO_STARE', true, false); 
            Configuration::updateValue('GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT','GPSR',false);
            Configuration::updateValue('GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT','<p>Produkty wprowadzone do obrotu przed 12/13/2024 nie muszą posiadać danych GPSR.</p>',true);

        return parent::install() && $this->registerHook('displayProductExtraContent');
    }
    
    public function uninstall()
    {
        Configuration::deleteByName('GAPSER_DOSTAWCA_WLACZ');
        Configuration::deleteByName('GAPSER_PRODUCENT_WLACZ');
        Configuration::deleteByName('GAPSER_WYLACZ_STARE');
        Configuration::deleteByName('GAPSER_NAZWA_ZAKLADKI_PRODUCENTA');
        Configuration::deleteByName('GAPSER_NAZWA_ZAKLADKI_DOSTAWCY');
        Configuration::deleteByName('GAPSER_WLACZ_INFO_STARE');
        Configuration::deleteByName('GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT');
        Configuration::deleteByName('GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT');
        return parent::uninstall();
    }

    public function getContent()
    {
        $id_shop = $this->context->shop->id;
        if (((bool)Tools::isSubmit('submitGapserModule')) == true) {
            $this->postProcess();
        } 
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->controller->addCSS($this->_path.'views/css/config.css', 'all');
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output.$this->renderForm();
    }
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGapserModule';
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

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Ustawienia'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="ikona-marki"></i>',
                        'name' => 'GAPSER_NAZWA_ZAKLADKI_PRODUCENTA',//brand
                        'label' => $this->l('Nazwa zakładki producenta'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Wyświetlić dane producenta'),
                        'name' => 'GAPSER_PRODUCENT_WLACZ',
                        'is_bool' => true,
                        'desc' => $this->l('Czy wyświetlić dane producenta?'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Tak')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Nie')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon-dostawcy"></i>',
                        'desc' => $this->l('Podaj nazwę zakładki dostawcy'),
                        'name' => 'GAPSER_NAZWA_ZAKLADKI_DOSTAWCY',//suplliper
                        'label' => $this->l('Nazwa zakładki dostawcy'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Wyświetlić dane dostawcy'),
                        'name' => 'GAPSER_DOSTAWCA_WLACZ',
                        'is_bool' => true,
                        'desc' => $this->l('Czy wyświetlić dane dostawcy?'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Tak')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Nie')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Włączyć produkty z przed 13/12/24?'),
                        'name' => 'GAPSER_WYLACZ_STARE',
                        'is_bool' => true,
                        'desc' => $this->l('Obowiązek GPSR dodtyczy produktów wprowadzonych po 13/12/24r.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Tak')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Nie')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Włączyć zakładkę z informacją dla produktów z przed 13/12/24?'),
                        'name' => 'GAPSER_WLACZ_INFO_STARE',
                        'is_bool' => true,
                        'desc' => $this->l('Czy wyświetlić ktrótką informację na tan temat?'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Tak')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Nie')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="ikona-marki"></i>',
                        'desc' => $this->l('Nazwa zakładki dla produktów z przed 13/12/24'),
                        'name' => 'GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT',//brand
                        'label' => $this->l('Podaj nazwę zakładki dla produktów z przed 13/12/24'),
                    ), 
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'desc' => $this->l('Krótkie info dla produktów z przed 13/12/24'),
                        //'prefix' => '<i class="ikona-marki"></i>',
                        'name' => 'GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT',//brand
                        'label' => $this->l('Wprowadź krótkie info dla produktów z przed 13/12/24r.'),
                    ),                  
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {    $id_shop= (int) $this->context->shop->id;
        return array(
            'GAPSER_NAZWA_ZAKLADKI_PRODUCENTA' => Configuration::get('GAPSER_NAZWA_ZAKLADKI_PRODUCENTA', 'Producent',false, null, $id_shop),
            'GAPSER_PRODUCENT_WLACZ' => Configuration::get('GAPSER_PRODUCENT_WLACZ', true, false, null, $id_shop),
            'GAPSER_NAZWA_ZAKLADKI_DOSTAWCY' => Configuration::get('GAPSER_NAZWA_ZAKLADKI_DOSTAWCY', 'Dostawca', false, null, $id_shop),
            'GAPSER_DOSTAWCA_WLACZ' => Configuration::get('GAPSER_DOSTAWCA_WLACZ', true, false, null, $id_shop),
            'GAPSER_WYLACZ_STARE' => Configuration::get('GAPSER_WYLACZ_STARE', true, false, null, $id_shop),
            'GAPSER_WLACZ_INFO_STARE' => Configuration::get('GAPSER_WLACZ_INFO_STARE'),
            'GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT' => Configuration::get('GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT','Brak danych GPSR',false, null, $id_shop),
            'GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT' => Configuration::get('GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT', 'Produkty wprowadzone do obrotu przed 12/13/2024 nie muszą posiadać danych GPSR',true, null, $id_shop),
        );
    }


    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        $id_shop= (int) $this->context->shop->id;
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key),null, null, $id_shop);
        }
    }

    public function hookDisplayProductExtraContent($params) {
        //var_dump($params);
        $id_shop=$this->context->shop->id;
        $idLang = Context::getContext()->language->id;
        if (isset($params['product']) && Validate::isLoadedObject($params['product'])) {
            $extraContents = [];
            $product = $params['product'];
            //var_dump($product); 
            if (Configuration::get('GAPSER_WYLACZ_STARE', true, false, null, $id_shop))
            {
            $do_kiedy = new DateTime('2024-12-14 00:00:00');
            $date_add = new DateTime($product->date_add);
            if ($date_add < $do_kiedy) 
                if (Configuration::get('GAPSER_WLACZ_INFO_STARE', true, false, null, $id_shop))            
                     {  $supplierDane = '<h3>Data wprowadzenia produktu: '.htmlspecialchars($product->date_add).'</h3>';
                        $supplierDane .= Configuration::get('GAPSER_TEXT_ZAKLADKI_STARY_PRODUKT', 'GPRS', false, null, $id_shop);
                        $extraContents[]=(new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
                        ->setTitle(Configuration::get('GAPSER_NAZWA_ZAKLADKI_STARY_PRODUKT', 'GPRS', false, null, $id_shop))
                        ->setContent($supplierDane);
                        return $extraContents; 
                     } 
            return;
        }
            
                      

            $supplierId = $product->id_supplier;
            //dostawca
            if ($supplierId and Configuration::get('GAPSER_DOSTAWCA_WLACZ', true, false, null, $id_shop)) {
                $supplier = new Supplier($supplierId);
                $id_adres = Address::getAddressIdBySupplierId($supplierId);
                $adres = new Address ($id_adres);
                $supplierDane = '<h3>' . htmlspecialchars($adres->name) . '</h3>';
                $supplierDane .= '<p>Email: ' . htmlspecialchars($adres->other) . '</p>';
                $supplierDane .= '<p>Adres: ' . htmlspecialchars($adres->address1) . ', ' . htmlspecialchars($adres->postcode) . ' ' . htmlspecialchars($adres->city) . '</p>';
                $supplierDane .= '<p>Kraj: ' . htmlspecialchars($adres->country) . '</p>';
                $supplierDane .= '<p>Telefon: ' . htmlspecialchars($adres->phone) . '</p>';
                $extraContents[]=(new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
                ->setTitle(Configuration::get('GAPSER_NAZWA_ZAKLADKI_DOSTAWCY', 'Importer', false, null, $id_shop))
                ->setContent($supplierDane);
            } 
            //producent
            $manufacturerId = $product->id_manufacturer;
            if ($manufacturerId and Configuration::get('GAPSER_PRODUCENT_WLACZ', true, false, null, $id_shop)) {
                $manufacturer = new Manufacturer($manufacturerId);
                $addresses = $manufacturer->getAddresses($idLang);
                $manufacturerDane = '<h3>' . htmlspecialchars($manufacturer->name) . '</h3>';
                $manufacturerDane .= '<p>Email: ' . htmlspecialchars($addresses[0]['other']) . '</p>';
                $manufacturerDane .= '<p>Adres: ' . htmlspecialchars($addresses[0]['address1']) . ', ' . htmlspecialchars($addresses[0]['postcode']) . ' ' . htmlspecialchars($addresses[0]['city']) . '</p>';
                $manufacturerDane .= '<p>Kraj: ' . htmlspecialchars($addresses[0]['country']) . '</p>';
                $manufacturerDane .= '<p>Telefon: ' . htmlspecialchars($addresses[0]['phone']) . '</p>';
                $extraContents[]=(new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
                ->setTitle(Configuration::get('GAPSER_NAZWA_ZAKLADKI_PRODUCENTA', 'Producent',false, null, $id_shop))
                ->setContent($manufacturerDane);
            } 
        
        return $extraContents;
    }
  }
}
