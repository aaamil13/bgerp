<?php



/**
 * Документ "Запитване"
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Inquiries2 extends embed_Manager
{
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cat_ProductDriverIntf';
	
	
	/**
	 * Как се казва полето за избор на вътрешния клас
	 */
	public $driverClassField = 'innerClass';
	

	/**
	 * Флаг, който указва, че документа е партньорски
	 */
	public $visibleForPartners = TRUE;
	
	
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, marketing_InquiryEmbedderIntf,colab_CreateDocumentIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inq';
    
    
    /**
     * Заглавие
     */
    public $title = 'Запитвания';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Запитване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, marketing_Wrapper, plg_Sorting, plg_Clone, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues, drdata_PhonePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Заглавие, personNames, company, email, folderId, createdOn, createdBy';
    
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.91|Търговия";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,marketing';
	
	
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canNew = 'every_one';
    
    
    /**
     * Кой има право да създава визитки на лица?
     */
    public $canMakeperson = 'ceo,crm,marketing';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId, personNames, title, company, email, place';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'marketing/tpl/SingleLayoutInquiryNew.shtml';
    
    
    /**
     * Шаблон за нотифициращ имейл (html)
     */
    public $emailNotificationFile = 'marketing/tpl/InquiryNotificationEmail.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/inquiry.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Опашка за записи, на които трябва да се изпратят нотифициращи имейли
     */
    protected $sendNotificationEmailQueue = array();
    
    
    /**
     * Кои външни(external) роли могат да създават/редактират документа в споделена папка
     */
    public $canWriteExternal = 'agent';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;

    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,proto';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'tel'     => 'clientData|lastDocUser',
    	'company' => 'clientData|lastDocUser',
    	'country' => 'clientData|lastDocUser|defMethod',
    	'pCode'   => 'clientData|lastDocUser',
    	'place'   => 'clientData|lastDocUser',
    	'address' => 'clientData|lastDocUser',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Шаблон,silent,input=hidden,refreshForm,placeholder=Популярни продукти,groupByDiv=»");
    	$this->FLD('title', 'varchar', 'caption=Заглавие,silent');
     
    	$this->FLD('quantities', 'blob(serialize,compress)', 'input=none,column=none');
    	$this->FLD('quantity1', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 1,hint=Въведете количество,input=none,formOrder=47');
    	$this->FLD('quantity2', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 2,hint=Въведете количество,input=none,formOrder=48');
    	$this->FLD('quantity3', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 3,hint=Въведете количество,input=none,formOrder=49');
    	$this->FLD('company', 'varchar(255)', 'caption=Контактни данни->Фирма,class=contactData,hint=Вашата фирма,formOrder=50');
    	$this->FLD('personNames', 'varchar(255)', 'caption=Контактни данни->Лице,class=contactData,hint=Вашето име||Your name,contragentDataField=person,formOrder=51,oldFieldName=name');
    	$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни данни->Държава,class=contactData,hint=Вашата държава,formOrder=52,contragentDataField=countryId,mandatory');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни данни->Имейл,class=contactData,hint=Вашият имейл||Your email,formOrder=53,mandatory');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Контактни данни->Телефони,class=contactData,hint=Вашият телефон,formOrder=54');
    	$this->FLD('pCode', 'varchar(16)', 'caption=Контактни данни->П. код,class=contactData,hint=Вашият пощенски код,formOrder=55');
        $this->FLD('place', 'varchar(64)', 'caption=Контактни данни->Град,class=contactData,hint=Населено място: град или село и община,formOrder=56');
        $this->FLD('address', 'varchar(255)', 'caption=Контактни данни->Адрес,class=contactData,hint=Вашият адрес,formOrder=57');
    	$this->FLD('inqDescription', 'richtext(rows=4,bucket=InquiryBucket)', 'caption=Вашето запитване||Your inquiry->Съобщение||Message');
    
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none');
    	$this->FLD('browser', 'varchar(80)', 'caption=UA String,input=none');
      	$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
    }


    /**
     * Разширява формата за редакция
     * 
     * @param stdClass $data
     * @return void
     */
    private function expandEditForm(&$data)
    { 
    	$form = &$data->form;
    	$form->setField('innerClass', "remember,removeAndRefreshForm=proto|measureId|meta");

    	// Ако има избран прототип, зареждаме му данните в река
    	if(isset($form->rec->proto)){
    		if($pRec = cat_Products::fetch($form->rec->proto)) {
    			if(is_array($pRec->driverRec)){
    				foreach ($pRec->driverRec as $fld => $value){
    					$form->rec->{$fld} = $value;
    				}
    			}
    		}
    	}
    	
    	$caption = 'Количества|*';
    	if(isset($data->Driver)){
    		$uomId = $form->rec->measureId;
    		if(isset($uomId) && $uomId != cat_UoM::fetchBySysId('pcs')->id){
    			$uom = cat_UoM::getShortName($uomId);
    		} else {
    			$uom = '';
    		}
    		
    		if(isset($form->rec->moq)){
    			$moq = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($form->rec->moq);
    			$caption .= "|* <small><i>( |Минимална поръчка|* " . $moq . " {$uom} )</i></small>";
    		}
    	}
    
    	// Добавяме полета за количество според параметрите на продукта
    	$quantityCount = &$form->rec->quantityCount;
    	if(!isset($quantityCount) || $quantityCount > 3 || $quantityCount < 0){
    		$quantityCount = 3;
    	}
    	
    	for($i = 1; $i <= $quantityCount; $i++){
    		$fCaption = ($quantityCount === 1) ? 'Количество' : "Количество|* {$i}";
    		$form->setField("quantity{$i}", "input,unit={$uom},caption={$caption}->{$fCaption}");
    	}
    	
    	$cu = core_Users::getCurrent('id', FALSE);
    	if(isset($cu) && !core_Users::isPowerUser()){
    		$personRec = crm_Profiles::getProfile($cu);
    		$emails = type_Emails::toArray($personRec->buzEmail);
    		$marketingEmail = count($emails) ? $emails[0] : $personRec->email;
    		$form->setDefault('personNames', $personRec->name);
    		$form->setDefault('email', $marketingEmail);
    	}
    	
    	$hide = (isset($cu) && core_Users::haveRole('partner', $cu)) ? TRUE : FALSE;
    	
    	$contactFields = $this->selectFields("#class == 'contactData'");
    	if(is_array($contactFields)){
    		foreach ($contactFields as $name => $value){
    			if($hide === TRUE){
    				$form->setField($name, 'input=hidden');
    			}
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;

    	if($form->rec->innerClass){
    		$protoProducts = doc_Prototypes::getPrototypes('cat_Products', $form->rec->innerClass);
            if(count($protoProducts)){
            	$form->setField('proto', 'input');
            	$form->setOptions('proto', $protoProducts);
            }
    	}
 
    	if(cls::load($form->rec->innerClass, TRUE)){
    		if($Driver = cls::get($form->rec->innerClass)){
    			if($moq = $Driver->getMoq()){
    				$form->rec->moq = $moq;
    			}

                if($form->rec->quantityCount === NULL && ($inqQuantity = $Driver->getInquiryQuantities()) !== NULL) {
                    $form->rec->quantityCount = $inqQuantity;
                }
    		}
    	}
    	
        $mvc->expandEditForm($data);

        if(haveRole('powerUser')) {
            $form->setField('personNames', 'mandatory=unsetValue');
            $form->setField('country', 'mandatory=unsetValue');
            $form->setField('email', 'mandatory=unsetValue');
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $folderClass);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->createdBy)){
    		$row->createdBy = '@anonym';
    	}
    	 
    	if (!Mode::is('text', 'plain') && !Mode::is('text', 'xhtml')){
            if($rec->email) {
    		    $row->email = "<div class='email'>{$row->email}</div>";
            }
    		$row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
    	}

        $row->brid = log_Browsers::getLink($rec->brid);
    	 
    	if($fields['-list']){
    		$row->title = $mvc->getTitle($rec);
    
    		$attr = array();
    		$attr['class'] = 'linkWithIcon';
    		$attr['style'] = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, $attr);
    	}
    	
    	if($Driver = $mvc->getDriver($rec->id)){
    		$measureId = $Driver->getDefaultUomId();
    	}
    	
    	if(isset($rec->measureId)){
    		$measureId = $rec->measureId;
    	}
    	
    	if(!$measureId){
    		$measureId = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID');
    	}
    	
    	if(!$measureId){
    		$measureId = cat_UoM::fetchBySinonim('pcs')->id;
    	}
    	
    	$shortName = tr(cat_UoM::getShortName($measureId));
    	
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	foreach (range(1, 3) as $i){
    		if(empty($rec->{"quantity{$i}"})){
    			if(isset($rec->quantities[$i - 1])){
    				$rec->{"quantity{$i}"} = $rec->quantities[$i - 1];
    				$row->{"quantity{$i}"} = $Double->toVerbal($rec->{"quantity{$i}"});
                    
    			}
    		}
    	}
        
        $cntQuantities = 0;
    	foreach (range(1, 3) as $i){
    		if($rec->{"quantity{$i}"}){
    			$row->{"quantity{$i}"} .= " {$shortName}";
                $cntQuantities++;
    		}
    	}
        
        if($cntQuantities > 1) {
            $row->q1Number = '1';
        }
    	
    	$row->time = core_DateTime::mysql2verbal($rec->createdOn);
    	
    	if(isset($rec->proto)){
    		$row->proto = cat_Products::getHyperlink($rec->proto);
    	}
    	
    	$row->innerClass = core_Classes::translateClassName($row->innerClass);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	// Изпращане на нотифициращ имейл само ако създателя не е контрактор
    	if($rec->createdBy == core_Users::ANONYMOUS_USER || empty($rec->createdBy)){
    		$mvc->sendNotificationEmailQueue[$rec->id] = $rec;
    	}
    	
    	// Ако запитването е в папка на контрагент вкарва се в група запитвания
    	$Cover = doc_Folders::getCover($rec->folderId);
    	if($Cover->haveInterface('crm_ContragentAccRegIntf')){
    		$groupId = crm_Groups::force('Клиенти » Запитвания');
    		$Cover->forceGroup($groupId, FALSE);
    	}
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
    	if(is_array($mvc->sendNotificationEmailQueue)){
    		foreach ($mvc->sendNotificationEmailQueue as $rec){
    		    try {
    		        $mvc->isSended = $mvc->sendNotificationEmail($rec);
    		        cat_Products::logDebug("Изпратен имейл за запитване създадено от '{$rec->createdBy}'", $rec->id);
    		    } catch (core_exception_Expect $e) {
                    self::logErr("Грешка при изпращане", $rec->id);
                    reportException($e);
                }
    		}
    	}
    }
    
    
    /**
     * Изпращане на нотифициращ имейл
     *
     * @param stdClass $rec
     */
    public function sendNotificationEmail($rec)
    {
    	// Взимат се нужните константи от пакета 'marketing'
    	$conf = core_Packs::getConfig('marketing');
    	$emailsTo = $conf->MARKETING_INQUIRE_TO_EMAIL;
    	$sentFromBox = $conf->MARKETING_INQUIRE_FROM_EMAIL;
    	
    	// Ако са зададено изходящ и входящ имейл се изпраща нотифициращ имейл
    	if($emailsTo && $sentFromBox){
    
    		// Имейла съответстващ на избраната кутия
    		$sentFrom = email_Inboxes::fetchField($sentFromBox, 'email');
    		
    		// Тяло на имейла html и text
    
    		$fields = $this->selectFields();
    		$fields['-single'] = TRUE;
    		
    		// Изпращане на имейл с phpmailer
    		$PML = email_Accounts::getPML($sentFrom);
    		    
    	   /*
    		* Ако не е зададено е 8bit
    		* Проблема се появява при дълъг стринг - без интервали и на кирилица.
    		* Понеже е entity се режи грешно от phpmailer -> class.smtpl.php - $max_line_length = 998;
    		*
    		* @see #Sig281
    		*/
    		$Driver = $this->getDriver($rec->id);
    		$body = $this->getDocumentBody($rec->id, 'xhtml');
    		$body = $body->getContent();
    		
    		// Създаваме HTML частта на документа и превръщаме всички стилове в inline
    		// Вземаме всичките css стилове
    
    		$css = file_get_contents(sbf('css/common.css', "", TRUE)) .
    		"\n" . file_get_contents(sbf('css/Application.css', "", TRUE));
    
    		$res = '<div id="begin">' . $body . '<div id="end">';
    
    		// Вземаме пакета
    		$conf = core_Packs::getConfig('csstoinline');
    
    		// Класа
    		$CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
    
    		// Инстанция на класа
    		$inst = cls::get($CssToInline);
    
    		// Стартираме процеса
    		$body =  $inst->convert($body, $css);
    		$body = str::cut($res, '<div id="begin">', '<div id="end">');
    		
    		$PML->Body = $body;
    		$PML->IsHTML(TRUE);
    		 
        	// Ембедване на изображенията
    		email_Sent::embedSbfImg($PML);
    		
    		$altText = $this->getDocumentBody($rec->id, 'plain');
    		$altText = $altText->getContent();
    		
    		Mode::push('text', 'plain');
    		$altText = html2text_Converter::toRichText($altText);
    		$altText = cls::get('type_Richtext')->toVerbal($altText);
    		Mode::pop('text');
    		
    		$PML->AltBody = $altText;
    		
    		// Име на фирма/лице/име на продукта
    		$subject = $this->getTitle($rec);
    		$PML->Subject = str::utf2ascii($subject);
    		
    		// Адрес на който да се изпрати
    		$PML->AddAddress($emailsTo);
    		$PML->AddCustomHeader("Customer-Origin-Email: {$rec->email}");
    		 
    		// От кой адрес е изпратен
    		$PML->SetFrom($sentFrom);
    		
    		if ($sendStatus = $PML->Send()) {
    		    // Задаваме екшъна за изпращането
                doclog_Documents::pushAction(
                    array(
                        'containerId' => $rec->containerId,
                        'threadId' => $rec->threadId,
                        'action' => doclog_Documents::ACTION_SEND,
                        'data' => (object)array(
                            'sendedBy' => core_Users::getCurrent(),
                            'from' => $sentFromBox,
                            'to' => $emailsTo
                        )
                    )
                );
                
                doclog_Documents::flushActions();
                marketing_Inquiries2::logWrite('АВТОМАТИЧНО изпращане на имейл', $rec->id);
    		} else {
    		    marketing_Inquiries2::logErr('Грешка при изпращане', $rec->id);
    		}
    		
    		// Изпращане
    		return $sendStatus;
    	}
    	 
    	return TRUE;
    }
    
    
    /**
     * Връща прикачените файлове
     */
   private function getAttachedFiles($rec, $Driver)
    {
    	$res = array();
    	
    	$fieldset = $this->getForm();
    	$Driver->addFields($fieldset);
    	$params = $fieldset->selectFields();
    	
    	$arr = (array)$rec;
    	foreach ($arr as $name => $value){
    		if($fieldset->getFieldType($name, FALSE) instanceof type_Richtext){
    			$files = fileman_RichTextPlg::getFiles($value);
    			$res = array_merge($res, $files);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    
    	return $self->getTitle($rec);
    }
    
    
    /**
     * Връща името на запитването
     */
    private function getTitle($id)
    {
    	$rec = $this->fetchRec($id);
 
    	$Driver = $this->getDriver($rec->id);
    	 
    	$name = $this->getFieldType('personNames')->toVerbal((($rec->company) ? $rec->company : $rec->personNames));
    	
    	$subject = "{$name} / $rec->title";
    	 
    	$Varchar = cls::get('type_Varchar');
    	 
    	return $Varchar->toVerbal($subject);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$data)
    {
        if(haveRole('partner')) {
            unset($data->row->ip, $data->row->time, $data->row->brid);
        }
    }

    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	if($rec->state == 'active' && !core_Users::isContractor()){
    
    		if($pId = cat_Products::fetchField("#originId = {$rec->containerId} AND #state = 'active'")){
    			$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    			$data->toolbar->addBtn("Артикул|* {$arrow}", array('cat_Products', 'single', $pId), "ef_icon=img/16/wooden-box.png,title=Преглед на артикул по това запитване");
    		} else {
    			// Създаване на нов артикул от запитването
    			if(cat_Products::haveRightFor('add', (object)array('folderId' => $rec->folderId, 'threadId' => $rec->threadId))){
    				$url = array('cat_Products', 'add', "innerClass" => $rec->innerClass, "originId" => $rec->containerId, 'ret_url' => TRUE);
    				if(doc_Folders::getCover($rec->folderId)->haveInterface('crm_ContragentAccRegIntf')){
    					$url['folderId'] = $rec->folderId; 
    					$url['threadId'] = $rec->threadId;
    				}
    				
    				$data->toolbar->addBtn('Артикул', $url, "ef_icon=img/16/wooden-box.png,title=Създаване на артикул по това запитване");
    			}
    		}
    
    		// Ако може да се създава лица от запитването се слага бутон
    		if($mvc->haveRightFor('makeperson', $rec)){
    			$companyId = doc_Folders::fetchCoverId($rec->folderId);
    			$data->toolbar->addBtn('Визитка на лице', array('crm_Persons', 'add', 'name' => $rec->personNames, 'buzCompanyId' => $companyId, 'country' => $rec->country), "ef_icon=img/16/vcard.png,title=Създаване на визитка с адресните данни на подателя");
    		}
    		
    		// Ако е настроено да се изпраща нотифициращ имейл, добавяме бутона за препращане
    		if($mvc->haveRightFor('sendemail', $rec)){
    			$conf = core_Packs::getConfig('marketing');
    			$data->toolbar->addBtn('Препращане', array($mvc, 'send', $rec->id), array('ef_icon'=> "img/16/email_forward.png", 'warning' => "Сигурни ли сте, че искате да препратите имейла на|* '{$conf->MARKETING_INQUIRE_TO_EMAIL}'",'title' => "Препращане на имейла със запитването към|* '{$conf->MARKETING_INQUIRE_TO_EMAIL}'"));
    		}
    	}
    }
    
    
    /**
     * Препраща имейл-а генериран от създаването на запитването отново
     */
    public function act_Send()
    {
    	$this->requireRightFor('sendemail');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('sendemail', $rec);
    	
    	$msg = '|Успешно препращане';
    	try {
    	    $this->sendNotificationEmail($rec);
    	    $this->logWrite('Ръчно препращане на имейл', $rec->id);
    	} catch (core_exception_Expect $e) {
            $this->logErr("Грешка при изпращане", $rec->id);
            reportException($e);
            $msg = "|Грешка при препращане";
        }
    	
    	return new Redirect(array($this, 'single', $rec->id), $msg);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	 
    	$row = new stdClass();
    	$row->title       = $this->getTitle($rec);
    	$row->authorId    = $rec->createdBy;
    	$row->author      = $rec->email;
    	$row->authorEmail = $rec->email;
    	$row->state       = $rec->state;
    	$row->recTitle    = $row->title;
    
    	return $row;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Кога може да се създава лице
    	if($action == 'makeperson' && isset($rec)){
    
    		// Ако корицата не е на фирма или състоянието не е активно никой не може
    		$cover = doc_Folders::getCover($rec->folderId);
    		if(!$cover->instance instanceof crm_Companies || $rec->state != 'active'){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'sendemail'){
    		$res = $mvc->getRequiredRoles('add', $rec, $userId);
    		
    		if(core_Users::isContractor()){
    			$res = 'no_one';
    		} else {
    			$conf = core_Packs::getConfig('marketing');
    			if(empty($conf->MARKETING_INQUIRE_TO_EMAIL) || empty($conf->MARKETING_INQUIRE_FROM_EMAIL)){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
    	$rec = $this->fetch($id);
    	$date = dt::mysql2verbal($rec->createdOn, 'd-M');
    	$time = dt::mysql2verbal($rec->createdOn, 'H:i');
    	
    	$tpl = new ET(tr("|Благодаря за Вашето запитване|*, |получено на|* {$date} |в|* {$time} |чрез нашия уеб сайт|*."));
    
    	return $tpl->getContent();
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
    	return array('crm_ContragentAccRegIntf');
    }
    
    
    /**
     * Състояние на нишката
     */
    public static function getThreadState($id)
    {
    	return 'opened';
    }
    
    
    /**
     * Екшън за добавяне на запитване от нерегистрирани потребители
     */
    function act_New()
    {
        Mode::set('showBulletin', FALSE);
        Request::setProtected('title,drvId,protos,moq,quantityCount,lg,measureId');
        
    	$this->requireRightFor('new');
    	expect($drvId = Request::get('drvId', 'int'));
    	$proto = Request::get('protos', 'varchar');
    	
    	$proto = keylist::toArray($proto);
        
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);

    	if(count($proto)){
            $sort = array();
    		foreach ($proto as $pId => &$name){
    			
    			// Ако прототипа е оттеглен или затворен, маха се от списъка
    			$pState = cat_Products::fetchField($pId, 'state');
    			if($pState != 'rejected' && $pState != 'closed'){
    				$name = cat_Products::getTitleById($pId, FALSE);
    			} else {
    				unset($proto[$pId]);
    			}
    		}
    	}

        asort($proto);

    	if($lg = Request::get('Lg')){
    		cms_Content::setLang($lg);
    		core_Lg::push($lg);
    	}
    	
    	$form = $this->prepareForm($drvId);
    	$form->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'input=hidden,silent');
    	$form->FLD('moq', 'double', 'input=hidden,silent');
    	$form->FLD('drvId', 'class', 'input=hidden,silent');
    	$form->FLD('quantityCount', 'double', 'input=hidden,silent');
    	$form->FLD('protos', 'varchar', 'input=hidden,silent');
    	
    	$mandatoryField = marketing_Setup::get('INQUIRE_MANDATORY_FIELDS');
    	if(in_array($mandatoryField, array('company', 'both'))){
    		$form->setField('company', 'mandatory');
    	}

    	if(in_array($mandatoryField, array('person', 'both'))){
    		$form->setField('personNames', 'mandatory');
    	}
    	
    	$form->input(NULL, 'silent');
    	
    	if(count($proto)){
    		$form->setOptions('proto', $proto);
    		if(count($proto) === 1){
    			$form->setDefault('proto', key($proto));
    			$form->setField('proto', 'input=hidden');
    		} else {
    			$form->setField('proto', 'input,caption=Шаблон,placeholder=Продукти||Products,groupByDiv=»');
    		}
    	} else {
    		$form->setField('proto', 'input=none');
    	}
    	
    	$data = (object)array('form' => $form);
    	
    	if(cls::load($form->rec->{$this->driverClassField}, TRUE)){

    		$Driver = cls::get($form->rec->{$this->driverClassField}, array('Embedder' => $this));
    		$data->Driver = $Driver;
    		
    		$Driver->addFields($data->form);
    		$this->expandEditForm($data);
    		
    		if($countryId = $this->getDefaultCountry($form->rec)){
    			$form->setDefault('country', $countryId);
    		} else {
    			$form->setField('country', 'input');
    		}
    		
    		$Driver->invoke('AfterPrepareEditForm', array($this, &$data, &$data));
    		
    		$form->input();
    		$this->invoke('AfterInputEditForm', array(&$form));
    	}
    	
    	$form->title = "|Запитване за|* <b>{$form->getFieldType('title')->toVerbal($form->rec->title)}</b>";

    	vislog_History::add("Форма за " . $form->getFieldType('title')->toVerbal($form->rec->title));

    	if(isset($form->rec->title)){
    		$form->setField('title', 'input=hidden');
    	}
    	
    	// След събмит на формата
    	if($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		$rec->state = 'active';
    		$rec->ip = core_Users::getRealIpAddr();
    		$rec->brid = log_Browsers::getBrid();
    		
    		// Винаги се рутира към правилната папка
    		$rec->folderId = marketing_InquiryRouter::route($rec);
    		
    		// Запис и редирект
    		if($this->haveRightFor('new')){
    		    
    		    vislog_History::add('Ново маркетингово запитване');
    		    
    			$cu = core_Users::getCurrent('id', FALSE);
    		    
    			// Ако няма потребител
    			if(!$cu){
        		    $contactFields = $this->selectFields("#class == 'contactData'");
                    $fieldNamesArr = array_keys($contactFields);
                    $userData = array();
                    foreach ((array)$fieldNamesArr as $fName) {
                        if (!trim($form->rec->{$fName})) continue;
                        $userData[$fName] = $form->rec->{$fName};
                    }
                    log_Browsers::setVars($userData);
    			}

                if($Driver) {
                    if($title = $Driver->getProductTitle($rec)) {
                        $rec->title = $title;
                    }
                }

    			$id = $this->save($rec);
    			doc_Threads::doUpdateThread($rec->threadId);
    			$this->logWrite("Създаване от е-артикул", $id);
    			
    			$singleUrl = self::getSingleUrlArray($id);
    			if(count($singleUrl)) return redirect($singleUrl, FALSE, '|Благодарим Ви за запитването', 'success');
    			
    			return followRetUrl(NULL, '|Благодарим Ви за запитването', 'success');
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/disk.png,title=Изпращане на запитването');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close-red.png,title=Oтказ');
    	$tpl = $form->renderHtml();
    	core_Form::preventDoubleSubmission($tpl, $form);
    	
    	// Поставяме шаблона за външен изглед
    	Mode::set('wrapper', 'cms_page_External');
    	
    	if($lg){
    		core_Lg::pop();
    	}
    	
        // Премахва зададения временно текущ език
        core_Lg::pop();
        
    	return $tpl;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		$moqVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->moq);
    		
    		// Ако няма въведени количества
    		if(empty($rec->quantity1) && empty($rec->quantity2) && empty($rec->quantity3)){
    			
    			// Ако има МОК, потребителя трябва да въведе количество, иначе се приема за еденица
    			if($rec->moq > 0){
    				$form->setError('quantity1,quantity2,quantity3', "Очаква се поне едно от количествата да е над||It is expected that at least one quantity is over|* <b>{$moqVerbal}</b>");
    			} else {
    				$rec->quantity1 = 1;
    			}
    		}
    		
    		// Ако има минимално количество за поръчка
    		$errorMoqs = $errorQuantities = $allQuantities = array();
    		
    		// Проверка на въведените количества
    		foreach (range(1, 3) as $i){
    			$quantity = $rec->{"quantity{$i}"};
    			if(empty($quantity)) continue;
    			
    			if($rec->moq > 0 && $quantity < $rec->moq){
    				$errorMoqs[] = "quantity{$i}";
    			}
    			
    			if(in_array($quantity, $allQuantities)){
    				$errorQuantities[] = "quantity{$i}";
    			} else {
    				$allQuantities[] = $quantity;
    			}
    		}
    		
    		if(count($errorMoqs)){
    			$form->setError(implode(',', $errorMoqs), "Количеството не трябва да е под||Quantity can't be bellow|* <b>{$moqVerbal}</b>");
    		}
    		
    		if(count($errorQuantities)){
    			$form->setError(implode(',', $errorQuantities), "Количествата трябва да са различни||Quantities must be different|*");
    		}
    	}
    }
    
    
    /**
     * Подготовка на формата за екшъна 'New'
     */
    private function prepareForm($drvId)
    {
    	$form = $this->getForm();
    	$form->rec->innerClass = $drvId;
    	$form->setField('innerClass', 'input=hidden');
    	 
    	$form->title = 'Запитване за поръчков продукт';
    	$cu = core_Users::getCurrent('id', FALSE);
    	 
    	// Ако има логнат потребител
    	if($cu && !haveRole('powerUser')){
    		$personId = crm_Profiles::fetchField("#userId = {$cu}", 'personId');
    		$personRec = crm_Persons::fetch($personId);
    		$inCharge = marketing_Router::getInChargeUser($rec->place, $rec->country);
    
    		// Ако лицето е обвързано с фирма, документа отива в нейната папка
    		if($personCompanyId = $personRec->buzCompanyId){
    			$form->rec->folderId = crm_Companies::forceCoverAndFolder((object)array('id' => $personCompanyId, 'inCharge' => $inCharge));
    		} else {
    			try{
    				expect($personRec || $personId, "Няма визитка на контрактор {$personId}");
    			} catch(core_exception_Expect $e){
    				crm_Persons::logErr('Няма визитка на контрактор', $personId);
    			}
    			 
    			// иначе отива в личната папка на лицето
    			$form->rec->folderId = crm_Persons::forceCoverAndFolder((object)array('id' => $personId, 'inCharge' => $inCharge));
    		}
    
    		$form->title .= " |в|*" . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
    
    		// Слагаме името на лицето, ако не е извлечено
    		$form->setDefault('personNames', $personRec->name);
    	}
    	 
    	// Ако няма потребител, но има бисквитка зареждаме данни от нея
    	if(!$cu){
    		$this->setFormDefaultFromCookie($form);
    	}
    	 
    	return $form;
    }


    /**
     * Ако има бисквитка с последно запитване, взима контактите данни от нея
     */
    private function setFormDefaultFromCookie(&$form)
    {
        $contactFields = $this->selectFields("#class == 'contactData'");
        $fieldNamesArr = array_keys($contactFields);
        
        $vars = log_Browsers::getVars($fieldNamesArr);
        
    	foreach ((array)$vars as $name => $val){
    		$form->setDefault($name, $val);
    	}
    }
    
    
    /**
     * Връща дефолт държавата на заданието
     */
    public static function getDefaultCountry($rec)
    {
    	if($cu = core_Users::getCurrent('id', FALSE)){
    		$profileRec = crm_Profiles::getProfile($cu);
    		if(isset($profileRec->country)) return $profileRec->country;
    	}
    	
    	if(cms_Content::getLang() == 'bg'){
    		$countryId = drdata_Countries::fetchField("#commonName = 'Bulgaria'");
    	} else {
    		$Drdata = cls::get('drdata_Countries');
    		$countryId = $Drdata->getByIp();
    	}
    
    	return $countryId;
    }
    
    
    /**
     * Намира кои полета са дошли от драйвера
     */
    public function getFieldsFromDriver($id)
    {
    	$rec = $this->fetchRec($id);
    	$Driver = $this->getDriver($rec);
    	
    	$form = $this->getForm();
    	$fieldsBefore = arr::make(array_keys($form->selectFields()), TRUE);
    	$Driver->addEmbeddedFields($form);
    	$fieldsAfter = arr::make(array_keys($form->selectFields()), TRUE);
    	
    	$params = array_diff_assoc($fieldsAfter, $fieldsBefore);
    	
    	return $params;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        // Допълваме данните само при създаване
        if($rec->id) return;

    	// Ако има оригинална дата на създаване, подменяме нея с текущата
    	if(isset($rec->oldCreatedOn)){
    		$rec->createdOn = $rec->oldCreatedOn;
    	}
    	
    	$rec->ip = core_Users::getRealIpAddr();
    	$rec->brid = log_Browsers::getBrid();
    	
    	if($rec->state != 'rejected'){
    		$rec->state = 'active';
    	}
      
        if(!strlen($rec->title)) {
            $Driver = cls::get($rec->innerClass);
            if($Driver) {
                if($title = $Driver->getProductTitle($rec)) {
                    $rec->title = $title;
                }
            }
        }
    }
    
    
    /**
     * Връща данните за запитванията
     * 
     * @param integer $id    - id' то на записа
     * @param email   $email - Имейл
     *
     * @return NULL|object
     */
    public static function getContragentData($id)
    {
        if (!$id) return ;
        
        $rec = self::fetch($id);
        
        $contrData = new stdClass();
        
        $contrData->person = $rec->personNames;
        $contrData->company = $rec->company;
        $contrData->tel = $rec->tel;
        $contrData->pCode = $rec->pCode;
        $contrData->place = $rec->place;
        $contrData->address = $rec->address;
        $contrData->email = $rec->email;
        $contrData->countryId = $rec->country;
        
        if ($contrData->countryId) {
            $contrData->country = self::getVerbal($rec, 'country');
        }
        
        return $contrData;
    }
}
