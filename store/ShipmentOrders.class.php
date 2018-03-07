<?php



/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания. Само складируеми продукти могат да се експедират
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrders extends store_DocumentMaster
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Експедиционни нареждания';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Exp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_ShipmentOrder, bgerp_DealIntf,trans_LogisticDataIntf,label_SequenceIntf=store_iface_ShipmentLabelImpl,deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter,store_Wrapper, sales_plg_CalcPriceDelta, plg_Sorting,store_plg_Request, acc_plg_Contable, cond_plg_DefaultValues,
                    plg_Clone,doc_DocumentPlg, plg_Printing, trans_plg_LinesPlugin, acc_plg_DocumentSummary, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, doc_SharablePlg,deals_plg_SetTermDate,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';

    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store,sales,purchase';


    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, folderId, currencyId, amountDelivered, amountDeliveredVat, weight, volume, createdOn, createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId,locationId,company,person,tel,pCode,place,address,note';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/EN.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ShipmentOrderDetails,store_DocumentPackagingDetail';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Експедиционно нареждане';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleStoreDocument.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.82|Търговия";
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_ShipmentOrderDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'delivery';
    
    
    /**
     * Показва броя на записите в лога за съответното действие в документа
     */
    public $showLogTimeInHead = 'Документът се връща в чернова=3';
    
    
    /**
     * 
     */
    public $printAsClientLayaoutFile = 'store/tpl/SingleLayoutPackagingListClient.shtml';
    
    
    /**
     * 
     */
    public $canAsclient = 'ceo,store,sales,purchase';
       

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ShipmentOrderDetails';
    
    
    /**
     * Шаблон за изглед при рендиране в транспортна линия
     */
    public $layoutFileInLine = 'store/tpl/ShortShipmentOrder.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	parent::setDocFields($this);

		$this->FLD('responsible', 'varchar', 'caption=Получил,after=deliveryTime');
    	$this->FLD('company', 'varchar', 'caption=Адрес за доставка->Фирма');
        $this->FLD('person', 'varchar', 'caption=Адрес за доставка->Име, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адрес за доставка->Тел., changable, class=contactData');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава, class=contactData');
        $this->FLD('pCode', 'varchar', 'caption=Адрес за доставка->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Адрес за доставка->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адрес за доставка->Адрес, changable, class=contactData');
        $this->setField('deliveryTime', 'caption=Натоварване');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	if(!isset($data->form->rec->id)){
    		expect($origin = static::getOrigin($data->form->rec), $data->form->rec);
    		if($origin->isInstanceOf('sales_Sales')){
    			$data->form->FNC('importProducts', 'enum(notshipped=Неекспедирани,stocked=Неекспедирани и налични,all=Всички)', 'caption=Вкарване от продажбата->Артикули, input,before=sharedUsers');
    		}
    	}
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	$tpl->append(sbf('img/16/toggle1.png', "'"), 'iconPlus');
    	if($data->rec->country){
    		$deliveryAddress = "{$data->row->country} <br/> {$data->row->pCode} {$data->row->place} <br /> {$data->row->address}";
			$inlineDeliveryAddress = "{$data->row->country},  {$data->row->pCode} {$data->row->place}, {$data->row->address}";
    	} else {
    		$deliveryAddress = $data->row->contragentAddress;
    	}

    	core_Lg::push($data->rec->tplLang);
    	$deliveryAddress = core_Lg::transliterate($deliveryAddress);
    	
    	$tpl->replace($deliveryAddress, 'deliveryAddress');
		$tpl->replace($inlineDeliveryAddress, 'inlineDeliveryAddress');

    	core_Lg::pop();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
    	core_Lg::push($rec->tplLang);
    	
    	if($row->pCode){
    		$row->deliveryTo .= $row->pCode;
    	}
    	
    	if($row->pCode){
    		$row->deliveryTo .= " " . core_Lg::transliterate($row->place);
    	}
    	
    	foreach(array('address', 'company', 'person', 'tel') as $fld){
    		if(!empty($rec->{$fld})){
    			if($fld == 'address'){
    				$row->{$fld} = core_Lg::transliterate($row->{$fld});
    			} elseif($fld == 'tel'){
    				if(callcenter_Talks::haveRightFor('list')){
    					$row->{$fld} = ht::createLink($rec->{$fld}, array('callcenter_Talks', 'list', 'number' => $rec->{$fld}));
    				}
    			}
    			
    			$row->deliveryTo .= ", {$row->{$fld}}";
    		}
    	}
    	
    	if(isset($rec->locationId)){
    		$row->locationId = crm_Locations::getHyperLink($rec->locationId);
    	}
    	
    	if(isset($rec->createdBy)){
    		$row->username = core_Users::fetchField($rec->createdBy, "names");
    		$row->username = core_Lg::transliterate($row->username);
    	}
    	
    	if(isset($fields['-single'])){
    		$logisticData = $mvc->getLogisticData($rec);
    		$logisticData['toCountry'] = ($rec->tplLang == 'bg') ? drdata_Countries::fetchField("#commonName = '{$logisticData['toCountry']}'", 'commonNameBg') : $logisticData['toCountry'];
    		$logisticData['toPCode'] = core_Lg::transliterate($logisticData['toPCode']);
    		$logisticData['toPlace'] = core_Lg::transliterate($logisticData['toPlace']);
    		$logisticData['toAddress'] = core_Lg::transliterate($logisticData['toAddress']);
    		$row->inlineDeliveryAddress = "{$logisticData['toCountry']}, {$logisticData['toPCode']} {$logisticData['toPlace']}, {$logisticData['toAddress']}";
			$row->toCompany = $logisticData['toCompany'];
    	}
    	
    	core_Lg::pop();
    	
    	$rec->palletCountInput = ($rec->palletCountInput) ? $rec->palletCountInput : static::countCollets($rec->id);
    	if(!empty($rec->palletCountInput)){
    		$row->palletCountInput = $mvc->getVerbal($rec, 'palletCountInput');
    	} else {
    		unset($row->palletCountInput);
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	$dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
        	$operations = $dealInfo->get('allowedShipmentOperations');
        	$operation = $operations['delivery'];
        	$rec->accountId = $operation['debit'];
        	$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        	
        	if($rec->locationId){
        		foreach (array('company','person','tel','country','pCode','place','address',) as $del){
        			 if($rec->{$del}){
        			 	$form->setError("locationId,{$del}", 'Не може да има избрана локация и въведени адресни данни');
        			 	break;
        			 }
        		}
        	}
        	
        	if((!empty($rec->tel) || !empty($rec->country)|| !empty($rec->pCode)|| !empty($rec->place)|| !empty($rec->address)) && (empty($rec->tel) || empty($rec->country)|| empty($rec->pCode)|| empty($rec->place)|| empty($rec->address))){
        		$form->setError('tel,country,pCode,place,address', 'Трябва или да са попълнени всички полета за адрес или нито едно');
        	}
        }
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareShipments($data)
    {
    	$data->shipmentOrders = parent::prepareLineDetail($data->masterData);
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderShipments($data)
    {
    	if(count($data->shipmentOrders)){
    		$tableMvc = clone $this;
    		$tableMvc->FNC('documentHtml', 'varchar', 'tdClass=mergedDetailWideTD');
    		$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    		$fields = "rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,palletCount=Палети,collection=Инкасиране,address=@Адрес";
    		if(Mode::is('printing')){
    			$fields .= ',documentHtml=@';
    		}
    		
    		$fields = core_TableView::filterEmptyColumns($data->shipmentOrders, $fields, 'collection,palletCount,documentHtml');
    		
    		return $table->get($data->shipmentOrders, $fields);
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
        $handle = $this->getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашето експедиционно нареждане") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
     	$tplArr[] = array('name' => 'Експедиционно нареждане', 
    					  'content' => 'store/tpl/SingleLayoutShipmentOrder.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderNarrow.shtml',
     					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
     	$tplArr[] = array('name' => 'Експедиционно нареждане с цени', 
     					  'content' => 'store/tpl/SingleLayoutShipmentOrderPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderPricesNarrow.shtml',
     					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,packPrice,discount,amount'));
     	$tplArr[] = array('name' => 'Packing list',
     					  'content' => 'store/tpl/SingleLayoutPackagingList.shtml', 'lang' => 'en', 'oldName' => 'Packaging list', 'narrowContent' => 'store/tpl/SingleLayoutPackagingListNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
     	$tplArr[] = array('name' => 'Експедиционно нареждане с декларация',
     					  'content' => 'store/tpl/SingleLayoutShipmentOrderDec.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutShipmentOrderDecNarrow.shtml',
     					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
     	$tplArr[] = array('name' => 'Packing list with Declaration',
    					  'content' => 'store/tpl/SingleLayoutPackagingListDec.shtml', 'lang' => 'en', 'oldName' => 'Packaging list with Declaration', 'narrowContent' => 'store/tpl/SingleLayoutPackagingListDecNarrow.shtml',
     					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
     	$tplArr[] = array('name' => 'Експедиционно нареждане с цени в евро',
     					  'content' => 'store/tpl/SingleLayoutShipmentOrderEuro.shtml', 'lang' => 'bg',
     					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));

    	$res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Изчислява броя колети в ЕН-то ако има
     * 
     * @param int $id - ид на ЕН
     * @return int $count- брой колети/палети
     */
    public static function countCollets($id)
    {
    	$rec = static::fetchRec($id);
    	$dQuery = store_ShipmentOrderDetails::getQuery();
    	$dQuery->where("#shipmentId = {$rec->id}");
    	$dQuery->where("#info IS NOT NULL");
    	$count = 0;
    	
    	$resArr = array();
    	while($dRec = $dQuery->fetch()){
            $rowNums =store_ShipmentOrderDetails::getLUs($dRec->info);
            if(is_array($rowNums)) {
                $resArr += $rowNums;
            }
    	}
    	 
    	// Връщане на броя на колетите
        if(count($resArr)) {
    	    $count = max($resArr);
        }
    	
    	return $count;
    }
    
    
    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec   - ид или запис на документ
     * @return array $data - логистичните данни
     *		
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма 
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма 
     *   	string|NULL   ['toPerson']     - лице
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     * 		varchar|NULL  ['ourReff']      - наш реф
     */
    public function getLogisticData($rec)
    {
    	$rec = $this->fetchRec($rec);
    	$res = parent::getLogisticData($rec);
    	
    	// Данните за разтоварване от ЕН-то са с приоритет
    	if(!empty($rec->country)|| !empty($rec->pCode)|| !empty($rec->place)|| !empty($rec->address)){
    		$res['toCountry'] = !empty($rec->country) ? drdata_Countries::fetchField($rec->country, 'commonName') : NULL;
    		$res['toPCode']   = !empty($rec->pCode) ? $rec->pCode : NULL;
    		$res['toPlace']   = !empty($rec->place) ? $rec->place : NULL;
    		$res['toAddress'] = !empty($rec->address) ? $rec->address : NULL;
    	}
    	
    	$res['toCompany'] = !empty($rec->company) ? $rec->company : $res['toCompany'];
    	$res['toPerson'] = !empty($rec->person) ? $rec->person : $res['toPerson'];
    	
    	unset($res['deliveryTime']);
    	$res['loadingTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : $rec->valior . " " . bgerp_Setup::get('START_OF_WORKING_DAY');
    	
    	
    	return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (($action == 'asclient') && $rec) {
            if (!trim($rec->company) && !trim($rec->person) && !$rec->country) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
     protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
     {
      	$rec = $data->rec;
     
    	if($rec->isReverse == 'no'){
    		
    		// Към чернова може да се генерират проформи, а към контиран фактури
    		if($rec->state == 'draft'){
    			
    			// Ако има проформа към протокола, правим линк към нея, иначе бутон за създаване на нова
    			if($iRec = sales_Proformas::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")){
    				if(sales_Proformas::haveRightFor('single', $iRec)){
    					$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    					$data->toolbar->addBtn("Проформа|* {$arrow}", array('sales_Proformas', 'single', $iRec->id, 'ret_url' => TRUE), 'title=Отваряне на проформа фактура издадена към експедиционното нареждането,ef_icon=img/16/proforma.png');
    				}
    			} else {
    				if(sales_Proformas::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))){
    					$data->toolbar->addBtn('Проформа', array('sales_Proformas', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на проформа фактура към експедиционното нареждане,ef_icon=img/16/proforma.png');
    				}
    			}
    		}
    		
    		if(deals_Helper::showInvoiceBtn($rec->threadId) && in_array($rec->state, array('draft', 'active', 'pending'))){
    				
    				// Ако има фактура към протокола, правим линк към нея, иначе бутон за създаване на нова
    				if($iRec = sales_Invoices::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")){
    					if(sales_Invoices::haveRightFor('single', $iRec)){
    						$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    						$data->toolbar->addBtn("Фактура|* {$arrow}", array('sales_Invoices', 'single', $iRec->id, 'ret_url' => TRUE), 'title=Отваряне на фактурата издадена към експедиционното нареждането,ef_icon=img/16/invoice.png');
    					}
    				} else {
    					if(sales_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))){
    						$data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на фактура към експедиционното нареждане,ef_icon=img/16/invoice.png,row=2');
    					}
    				}
    			}
    		}
    		
    		// Бутони за редакция и добавяне на ЧМР-та
    		if(in_array($rec->state, array('active', 'pending'))) {
    			if($cmrId = trans_Cmrs::fetchField("#originId = {$rec->containerId} AND #state != 'rejected'")){
    				if(trans_Cmrs::haveRightFor('single', $cmrId)){
    					$data->toolbar->addBtn("ЧМР", array('trans_Cmrs', 'single', $cmrId, 'ret_url' => TRUE), "title=Преглед на|* #CMR{$cmrId},ef_icon=img/16/lorry_go.png");
    				}
    			} elseif(trans_Cmrs::haveRightFor('add', (object)array('originId' => $rec->containerId))){
    				$data->toolbar->addBtn("ЧМР", array('trans_Cmrs', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на ЧМР към експедиционното нареждане,ef_icon=img/16/lorry_add.png');
    			}
    		}
    	}
}
