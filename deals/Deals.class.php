<?php
/**
 * Клас 'deals_Deals'
 *
 * Мениджър за финансови сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Deals extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Финансови сделки';


    /**
     * Абревиатура
     */
    public $abbr = 'Fd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_RegisterIntf, doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, deals_DealsAccRegIntf, bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, plg_Printing, doc_DocumentPlg, plg_Search, doc_plg_BusinessDoc, doc_ActivatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,deals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,deals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,deals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,deals';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,deals';
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,dealName,accountId,folderId,state,createdOn';
    

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Финансова сделка';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "4.9|Финанси";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'deals/tpl/SingleLayoutDeals.shtml';
    
    
    /**
     * Позволени операции в платежните документи
     */
    public $allowedPaymentOperations = array();
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'dealName';
    
    
    /**
     * Брой детайли на страница
     */
    public $listDetailsPerPage = 20;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'dealName, accountId, description';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('dealName', 'varchar(255)', 'caption=Наименование,mandatory,width=100%');
    	$this->FLD('accountId', 'acc_type_Account(regInterfaces=deals_DealsAccRegIntf, allowEmpty,select=)', 'caption=Сметка,mandatory');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Име');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Валута->Код');
    	$this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=4em');
    	$this->FLD('description', 'richtext(rows=4)', 'caption=Допълнителno->Описание');
    	
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен)','caption=Статус, input=none');
    	
    	$this->setDbUnique('dealName');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	
    	$form->setDefault('contragentClassId', $coverClass::getClassId());
    	$form->setDefault('contragentId', $coverId);
    	
    	$form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
    	$form->setReadOnly('contragentName');
    	
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()){
    		$rec  = &$form->rec;
    		if(!$rec->currencyRate){
    			// Изчисляваме курса към основната валута ако не е дефиниран
    			$rec->currencyRate = round(currency_CurrencyRates::getRate(dt::now(), $rec->currencyId, NULL), 4);
    			
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, dt::now(), $rec->currencyId, NULL)){
    				$form->setWarning('currencyRate', $msg);
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$row->id}</b> ({$row->state})";
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'AggregateDealInfo', $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг,row=2');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$data->masterMvc = cls::get('cash_Cases');
    	$data->masterId = $data->rec->id;
    	
    	$mvc->getHistory($data);
    }
    
    
    /**
     * Връща хронологията от журнала, където участва документа като перо
     */
    private function getHistory(&$data)
    {
    	$rec = $this->fetchRec($data->rec->id);
    	
    	//$accId = '501';
    	//$item = acc_Items::fetchItem(cash_Cases::getClassId(), 1);
    	//$rec->createdOn = NULL;
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$item = acc_Items::fetchItem($this->getClassId(), $rec->id);
    	$blAmount = 0;
    	
    	// Ако документа е перо
    	if($item){
    		$data->history = array();
    		
    		// Намираме от журнала записите, където участва перото от датата му на създаване до сега
    		$jQuery = acc_JournalDetails::getQuery();
    		acc_JournalDetails::filterQuery($jQuery, $rec->createdOn, dt::now(), $accId, $item->id, 144);
    		
    		$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    		$Pager->itemsCount = $jQuery->count();
    		$Pager->calc();
    		$data->pager = $Pager;
    		
    		// Извличаме всички записи, за да изчислим точно крайното салдо
    		$count = 0;
    		while($jRec = $jQuery->fetch()){
    			$start = $data->pager->rangeStart;
    			$end = $data->pager->rangeEnd - 1;
    			
    			$row = new stdClass();
    			try{
    				$DocType = cls::get($jRec->docType);
    				$row->docId = $DocType->getHyperLink($jRec->docId, TRUE);
    			} catch(Exception $e){
    				$row->docId = "<span style='color:red'>" . tr('Проблем при показването') . "</span>";
    			}
    			
    			$jRec->amount /= $rec->currencyRate;
    			if($jRec->debitItem1 == $item->id){
    				$row->debitA = $Double->toVerbal($jRec->amount);
    				$blAmount += $jRec->amount;
    			} elseif($jRec->creditItem1 == $item->id){
    				$row->creditA = $Double->toVerbal($jRec->amount);
    				$blAmount -= $jRec->amount;
    			}
    		
    			$count++;
    			
    			// Ще показваме реда, само ако отговаря на текущата страница
    			if(empty($data->pager) || ($count >= $start && $count <= $end)){
    				$data->history[] = $row;
    			}
    		}
    	}
    	
    	// Обръщаме във вербален вид изчисленото крайно салдо
    	$data->row->blAmount = $Double->toVerbal($blAmount);
    	if($blAmount < 0){
    		$data->row->blAmount = "<span style='color:red'>{$data->row->blAmount}</span>";
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$fieldSet = new core_FieldSet();
    	$fieldSet->FLD('docId', 'varchar', 'tdClass=large-field');
    	$fieldSet->FLD('debitA', 'double');
    	$fieldSet->FLD('creditA', 'double');
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet, 'class' => 'styled-table'));
    	$table->tableClass = 'listTable';
    	$fields = "docId=Документ,debitA=Дебит->Сума ({$data->row->currencyId}),creditA=Кредит->Сума ({$data->row->currencyId})";
    	$tpl->append($table->get($data->history, $fields), 'DETAILS');
    	
    	if($data->pager){
    		$tpl->replace($data->pager->getHtml(), 'PAGER');
    	}
    }
    
    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * 
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * @param int $id key(mvc=deals_Deals)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = static::getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $this->singleTitle . "№{$rec->id}",
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title,
    	);
    
    	return $row;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = self::fetchRec($id);
    
    	$result = new bgerp_iface_DealResponse();
    
    	$result->dealType = bgerp_iface_DealResponse::TYPE_DEAL;
    	
    	$result->allowedPaymentOperations = $this->allowedPaymentOperations;
    	
    	$result->paid->currency = $rec->currencyId;
    	$result->paid->rate = $rec->currencyRate;
    	
    	return $result;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази сделка
     *
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
     * момента.
     *
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за сделката е
     * сечението на следните множества:
     *
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     *
     * @return bgerp_iface_DealResponse
     */
    public function getAggregateDealInfo($id)
    {
    	$dealRec = self::fetchRec($id);
    	 
    	$dealDocuments = $this->getDescendants($dealRec->id);
    
    	// Извличаме dealInfo от самата сделка
    	/* @var $saleDealInfo bgerp_iface_DealResponse */
    	$dealDealInfo = $this->getDealInfo($dealRec->id);
    
    	// dealInfo-то на самата сделка е база, в/у която се натрупват някой от аспектите
    	// на породените от нея платежни документи
    	$aggregateInfo = clone $dealDealInfo;
    	
    	if(count($dealDocuments)){
    		/* @var $d core_ObjectReference */
    		foreach ($dealDocuments as $d) {
    			$dState = $d->rec('state');
    			
    			// Игнорираме черновите и оттеглените документи
    			if ($dState == 'draft' || $dState == 'rejected') return;
    		
    			if ($d->haveInterface('bgerp_DealIntf')) {
    				$dealInfo = $d->getDealInfo();
    				$aggregateInfo->paid->push($dealInfo->paid);
    			}
    		}
    	}
    	
    	return $aggregateInfo;
    }
    
    
    /**
     * Дебъг екшън показващ агрегираните бизнес данни
     */
    function act_AggregateDealInfo()
    {
    	requireRole('debug');
    	expect($id = Request::get('id', 'int'));
    	$info = $this->getAggregateDealInfo($id);
    	bp($info->allowedPaymentOperations,$info->paid);
    }
}