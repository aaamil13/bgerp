<?php 

/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_Declarations extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Заглавие
     */
    public $title = 'Декларации за съответствие';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Декларация за съответствие';
    
    
    /**
     * Заглавие на менюто
     */
    public $pageMenu = 'Декларации';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, doc_plg_TplManager, bgerp_plg_Blank, recently_Plugin, doc_ActivatePlg, plg_Printing, cond_plg_DefaultValues, 
    				 plg_RowTools2, doc_DocumentIntf, doc_DocumentPlg, doc_EmailCreatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,dec';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,dec';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,dec';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,dec';
    
    
    /**
     * Кои полета ще виждаме в листовия изглед
     */
    public $listFields = 'id, doc, createdOn, createdBy';
    
    
    /**
     * Кой е тетущият таб от менюто
     */
    public $currentTab = 'Декларации';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'dec/tpl/SingleLayoutDeclarations.shtml';
    
    
    /**
     * В кой плейсхолдер да се сложи шаблона
     */
    public $templateFld = 'content';

    
    /**
     * Абревиатура
     */
    public $abbr = 'Dec';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'doc, declaratorName, id';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'materials' => 'lastDocUser|lastDoc|lastDocSameCountry',
        'statements' => 'lastDocUser|lastDoc|lastDocSameCountry|sessionValue',
    );
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        // номера на документа
        $this->FLD('doc', 'key(mvc=doc_Containers)', 'caption=Към документ, input=none');

        // Заглавие, което да излиза в документа
        $this->FLD('documentTitle', 'varchar', 'caption=Заглавие на документ');
        
        // дата на декларацията
        $this->FLD('date', 'date', 'caption=Дата');
        
        // декларатор
        $this->FLD('declaratorName', 'varchar', 'caption=Представлявана от->Име, mandatory,recently,remember');

        // позицията на декларатора
        $this->FLD('declaratorPosition', 'varchar', 'caption=Представлявана от->Позиция, mandatory,recently,remember');

        // допълнителни пояснения
        $this->FLD('explanation', 'varchar', 'caption=Представлявана от->Допълнително, recently, remember');
        
        // продукти, идват от фактурата
        $this->FLD('productId', 'set', 'caption=Продукти->Продукти, maxColumns=2');
        
        $this->FLD('inv', 'int', 'caption=Фактура, input=none');
        
        // на какви твърдения отговарят
        $this->FLD('statements', 'keylist(mvc=dec_Statements,select=title)', 'caption=Твърдения->Отговарят на, mandatory,remember');
        
        // от какви материали е
        $this->FLD('materials', 'keylist(mvc=dec_Materials,select=title)', 'caption=Материали->Изработени от, mandatory,remember');
        
        // допълнителен текст
        $this->FLD('note', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки->Допълнения');
        
        // поле събирателно за плейсхолдерите
        $this->FLD('formatParams', 'blob(serialize, compress)', 'caption=Параметри, title=Параметри за конвертиране на шаблона, input=none');
    }
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        // рефрешваме формата при избор на шаблон
        $mvc->setField('template', 'silent,removeAndRefreshForm=statements');
    }


    /**
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    
        // Масива, който ще връщаме
        static $placesArr = array();
        
        // Проверяваме имаме ли зареден шаблон
        if($data->form->rec->template) {
            // кой е езика на шаблона
            $lang = doc_TplManager::fetch($form->rec->template)->lang;
        
            // зареждаме шаблонха
            $tpl = doc_TplManager::getTemplate($data->form->rec->template); 
            // взимаме всичките плейсхолдери на шаблона
            $allPlaceholders = label_Templates::getPlaceholders($tpl->content);
       
            // обхождаме плейсхолдерите
            if(is_array($allPlaceholders)) {
                foreach ($allPlaceholders as $pA) {
                    // Правим имената на плейсхолдерите с главна буква,
                    // за да нямаме дублиране с FLD
                    $p = "_".($pA);
                    
                    // При показване името на полето "_" я заменяме с интервал
                    $p1 = str_replace("_", " ", $p);
                 
                    // Правим функционални (виртуални) полета
                    $form->FNC("{$p}", 'varchar(255)', "caption=Параметри->{$p1},input=input, silent,recently");
                    $form->input();

                    $placesArr[$p] = $form->rec->{$p};
                }
            } 
        }
   
        // Записваме blob полето
        $data->form->rec->formatParams  = (array) $placesArr;
       
        // Вземаме данните от предишния запис
        $dataArr = $data->form->rec->formatParams;
        
        // Обхождаме масива
        foreach ((array) $dataArr as $fieldName => $value) { 
            
            // Добавяме данните от записите
            $data->form->rec->$fieldName = $value;
        }
                
        // Записваме оригиналното ид, ако имаме такова
        if ($data->form->rec->originId) {
            $data->form->setDefault('doc', $data->form->rec->originId);

            // и е по  документ фактура намираме кой е той
            $doc = doc_Containers::getDocument($data->form->rec->originId);
            $class = $doc->className;
            $dId = $doc->that;
            $rec = $class::fetch($dId);

            // взимаме продуктите от детаийла на фактурата
            $dQuery = sales_InvoiceDetails::getQuery();
            $dQuery->where("#invoiceId = {$rec->id}");
            
            $statements = "";
            
            while ($dRec = $dQuery->fetch()) { 
                $productName[$dRec->productId] = cat_Products::getTitleById($dRec->productId);
                // твърдения
                $st = cat_Products::getParams($dRec->productId, 'decStatements');
                // обединяваме твърденията
                $statements = keylist::merge ($statements,$st);
            }
            
            $staArr = array();
            //масив от твърденията
            $statementArr = keylist::toArray($statements);
            
            foreach($statementArr as $st) { 
                // фечваме езика на твърдението
                $lgStatement = dec_Statements::fetch($st)->lg;
          
                // ако нямаме зададен език на трърдението и нямаме зададен език на шаблона
                // премахваме тези твърдения, които имат зададен език
                if($lang !== "" && $lgStatement!== "") {
                    unset($statementArr[$st]);
                    $statements = keylist::fromArray($statementArr);
                }
                
                // ако зададения език на шаблона и езика на твърдението съвпадат
                // правим нов лист с твърдения
                if($lgStatement!== "" && $lgStatement == $lang) {
                    $staArr[$st] = $st;
                    $statements = keylist::fromArray($staArr);
                }
            }

            $data->form->setSuggestions('productId', $productName);
            $data->form->setDefault('statements', $statements);
            $data->form->setDefault('inv', $rec->id);
        }

        // слагаме Управители
        $hr = cls::get('hr_EmployeeContracts');

        $managers = $mvc->getManagers();

        if (countR($managers) > 0) {
            $data->form->setSuggestions('declaratorName', $managers);
        }

        // ако не е указана дата взимаме днешната
        if (!$data->form->rec->date) {
            $data->form->setDefault('date', dt::now(false));
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    
        try {
            $row->doc = doc_Containers::getLinkForSingle($rec->doc);
        } catch (core_exception_Expect $e) {
            $row->doc = tr('Проблем при показването');
        }

        $rec->tplLang = $mvc->pushTemplateLg($rec->template);
        
        if (!$rec->documentTitle) {
            $row->documentTitle = doc_TplManager::getTitleByid($rec->template);
        }
  
        // Зареждаме данните за собствената фирма
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        // Адреса на фирмата
        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= ', ' . $ownCompanyData->address;
        }

        $Varchar = cls::get('type_Varchar');

        // името на фирмата
        $row->MyCompany = crm_Companies::getTitleById($ownCompanyData->companyId);
        $row->MyCompany = transliterate(tr($row->MyCompany));

        // държавата
        $fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
        $row->MyCountry = drdata_Countries::getVerbal($ownCompanyData->countryId, $fld);

        // адреса
        $row->MyAddress = $Varchar->toVerbal($address);
        $row->MyAddress = transliterate(tr($row->MyAddress));

        // Ват номера й
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if ($uic != $ownCompanyData->vatNo) {
            $row->MyCompanyVatNo = ' ' . $ownCompanyData->vatNo;
        }

        if ($uic) {
            $row->uicId = ' ' . $uic;
        }

        // информация за управителя/декларатора
        if ($rec->declaratorName) {
            $row->manager = $rec->declaratorName;

            if (is_numeric($rec->declaratorName)) {
                if ($declaratorData = crm_Persons::fetch($rec->declaratorName)) {
                    $row->manager = $declaratorData->name;
                    $row->{'managerEGN'} = $declaratorData->egn;
                }
            }

            $row->manager = transliterate(tr($row->manager));

            $row->declaratorName = transliterate(tr($rec->declaratorName));

            $row->declaratorPosition = transliterate(tr($rec->declaratorPosition));
        }

        if ($rec->date == null) {
            $row->date = $rec->createdOn;
        } else {
            if (core_Lg::getCurrent() == 'bg') {
                $row->date = dt::mysql2verbal($rec->date, 'd.m.Y') . tr('|г.|*');
            } else {
                $row->date = dt::mysql2verbal($rec->date, 'd.m.Y');
            }
        }

        // вземаме избраните продукти
        if ($rec->productId) {
            $products = arr::make($rec->productId);

            $batches = array();
            $classProduct = array();

            if ($rec->inv) {
                $dQuery = sales_InvoiceDetails::getQuery();
                $dQuery->where("#invoiceId = {$rec->inv}");

                while ($dRec = $dQuery->fetch()) {
                    $batches[$dRec->productId] = $dRec->batches;
                }
            }

            foreach ($products as $product) {
                $classProduct[$product] = explode('|', $product);
            }

            $row->products = '<ol>';
            foreach ($classProduct as $iProduct => $name) {
                $pId = (isset($name[1])) ? $name[1] : $name[0];
                $productName = cat_Products::getTitleById($pId);
                if (($batches[$pId])) {
                    $row->products .= '<li>'.$productName . ' - '. $batches[$pId] .'</li>';
                } else {
                    $row->products .= '<li>'.$productName.'</li>';
                }
            }
            $row->products .= '</ol>';
        }

        // ако декларацията е към документ
        if ($rec->originId) {
            // и е по  документ фактура намираме кой е той
            $doc = doc_Containers::getDocument($rec->originId);
            
            $class = $doc->className;
            $dId = $doc->that;
            $recOrigin = $class::fetch($dId);
            
            // Попълваме данните от контрагента. Идват от фактурата
            $addressContragent = trim($recOrigin->contragentPlace . ' ' . $recOrigin->contragentPCode);
            if ($addressContragent && !empty($recOrigin->contragentAddress)) {
                $addressContragent .= ', ' . $recOrigin->contragentAddress;
            }
            $row->contragentCompany = cls::get($recOrigin->contragentClassId)->getTitleById($recOrigin->contragentId);
            $row->contragentCompany = transliterate(tr($row->contragentCompany));

            $fld = ($rec->tplLang == 'bg') ? 'commonNameBg' : 'commonName';
            $row->contragentCountry = drdata_Countries::getVerbal($recOrigin->contragentCountryId, $fld);

            $row->contragentAddress = $Varchar->toVerbal($addressContragent);
            $row->contragentAddress = transliterate(tr($row->contragentAddress));

            $uicContragent = drdata_Vats::getUicByVatNo($recOrigin->contragentVatNo); 
            if ($uic != $recOrigin->contragentVatNo) {
                $row->contragentCompanyVatNo = $Varchar->toVerbal($recOrigin->contragentVatNo);
            }
            
            if($recOrigin->uicNo) {
                $row->contragentUicId = $recOrigin->uicNo;
            } else {
                $row->contragentUicId = $uicContragent;
            }

            $invoiceNo = str_pad($recOrigin->number, '10', '0', STR_PAD_LEFT) . ' / ' . dt::mysql2verbal($recOrigin->date, 'd.m.Y');
            $row->invoiceNo = $invoiceNo;
        }

        // вземаме материалите
        if ($rec->materials) {
            $materials = type_Keylist::toArray($rec->materials);

            $row->material = '';
            foreach ($materials as $material) {
                $row->material .= '<li>' . dec_Materials::getVerbal($material, 'text') . '</li>';
            }
        }

        // вземаме твърденията
        if ($rec->statements) {
            $statements = type_Keylist::toArray($rec->statements);

            $row->statements = '';
            foreach ($statements as $statement) {
                $row->statements .= '<li>' . dec_Statements::getVerbal($statement, 'text') . '</li>';
            }
        }

        // ако има допълнителни бележки
        if ($rec->note) {
            $row->note = $mvc->getVerbal($rec, 'note');
        }
      
        // Ако имаме въведени стойности във FNC полетата
        // ще ги покажем в шаблона
        if(is_array($rec->formatParams)) {
            foreach ($rec->formatParams as $placeholder => $value) { 
                if(strlen($value) !== 0) {
                    if(strpos($placeholder, "_") == 0) {
                        $placeholder = substr($placeholder, 1);
                    }
                   
                    $row->$placeholder = $Varchar->toVerbal($value);
                }
            }
        }

        core_Lg::pop();
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        switch ($action) {

            case 'activate':
                if (empty($rec->id)) {
                    // не се допуска активиране на незаписани декларации
                    $requiredRoles = 'no_one';
                }
                break;
            case 'add':
                if (empty($rec->originId)) {
                    $requiredRoles = 'no_one';
                } else {
                    $origin = doc_Containers::getDocument($rec->originId);

                    if (!$origin->isInstanceOf('sales_Invoices')) {
                        $requiredRoles = 'no_one';
                    } else {
                        $originRec = $origin->rec();
                        if ($originRec->state != 'active' || $originRec->type != 'invoice') {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
        }
    }


    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $mvc->currentTab = 'Декларации->Списък';
        $mvc->menuPage = 'Търговия:Продажби';
    }


    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/


    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();

        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;

        return $row;
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля, запознайте се с нашата декларация за съответствие') . ': #[#handle#]');
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }


    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Декларация за съответствие',    'content' => 'dec/tpl/AgreementDeclaration.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Приложение №1',   'content' => 'dec/tpl/Application1.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Приложение №5',      'content' => 'dec/tpl/Application5.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Declaration of compliance',         'content' => 'dec/tpl/DeclarationOfCompliance.shtml', 'lang' => 'en');


        return doc_TplManager::addOnce($this, $tplArr);
    }


    /**
     * Метод по подразбиране за намиране на дефолт шаблона
     */
    public function getDefaultTemplate_($rec)
    {
        $cData = doc_Folders::getContragentData($rec->folderId);
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');

        $conf = core_Packs::getConfig('dec');
        $def = (empty($cData->countryId) || $bgId === $cData->countryId) ? $conf->DEC_DEF_TPL_BG : $conf->DEC_DEF_TPL_EN;

        return $def;
    }


    /**
     * Връща всички Всички лица, които могат да бъдат титуляри на сметка
     * тези включени в група "Управители"
     */
    public function getManagers()
    {
        $options = array();
        $groupId = crm_Groups::fetchField("#sysId = 'managers'", 'id');
        $personQuery = crm_Persons::getQuery();
        $personQuery->where("#groupList LIKE '%|{$groupId}|%'");

        while ($personRec = $personQuery->fetch()) {
            //$options[$personRec->id] = crm_Persons::getVerbal($personRec, 'name');
            $options[crm_Persons::getVerbal($personRec, 'name')] = crm_Persons::getVerbal($personRec, 'name');
        }

        return $options;
    }
}
