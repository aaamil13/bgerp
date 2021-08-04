<?php


/**
 * Документ "Изходяща оферта"
 *
 * Мениджър на документи за Изходящи оферти
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Quotations extends deals_QuotationMaster
{
    /**
     * Заглавие
     */
    public $title = 'Изходящи оферти';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Q';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Sorting, sales_Wrapper, doc_plg_Close, doc_EmailCreatePlg, acc_plg_DocumentSummary, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, plg_Printing, doc_ActivatePlg, plg_Clone, bgerp_plg_Blank, cond_plg_DefaultValues,doc_plg_SelectFolder,plg_LastUsedKeys,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,sales';

    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/quotation.png';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canWrite = 'ceo,sales';

    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'sales_QuotationsDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Изходяща оферта';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.7|Търговия';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_QuotationsDetails';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, sales';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo, sales';
    
    
    /**
     * Кой  може да клонира системни записи
     */
    public $canClonesysdata = 'ceo, sales';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,sales';


    /**
     * Клас за сделка, който последва офертата
     */
    protected $dealClass = 'sales_Sales';


    /**
     * Кои полета да са нередактируеми, ако има вече детайли
     */
    protected $readOnlyFieldsIfHaveDetail = 'chargeVat,currencyRate,currencyId,deliveryTermId,deliveryPlaceId,deliveryAdress,deliveryCalcTransport';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setQuotationFields($this);
        $this->FLD('expectedTransportCost', 'double', 'input=none,caption=Очакван транспорт');
        
        $this->FNC('row1', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row2', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->FNC('row3', 'complexType(left=Количество,right=Цена)', 'caption=Детайли->Количество / Цена');
        $this->setField('paymentMethodId', 'salecondSysId=paymentMethodSale');
        $this->setField('chargeVat', 'salecondSysId=quotationChargeVat');
        $this->setField('deliveryTermId', 'salecondSysId=deliveryTermSale');
        $this->FLD('deliveryCalcTransport', 'enum(yes=Скрит транспорт,no=Явен транспорт)', 'input=none,caption=Доставка->Начисляване,after=deliveryTermId');

        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'after=validFor,caption=Допълнително->Цени,notChangeableByContractor');
        $this->FLD('others', 'text(rows=4)', 'caption=Допълнително->Условия');
    
        $this->setDbIndex('date');
        $this->setDbIndex('contragentClassId,contragentId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setOptions('priceListId', array('' => '') + price_Lists::getAccessibleOptions($rec->contragentClassId, $rec->contragentId));

        if (isset($rec->originId) && $data->action != 'clone' && empty($form->rec->id)) {
            
            // Ако офертата има ориджин
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->haveInterface('cat_ProductAccRegIntf')) {
                $form->setField('row1,row2,row3', 'input');
                $rec->productId = $origin->that;
                
                if($Driver = $origin->getDriver()){
                    $quantitiesArr = $Driver->getQuantitiesForQuotation($origin->getInstance(), $origin->fetch());
                    $form->setDefault('row1', $quantitiesArr[0]);
                    $form->setDefault('row2', $quantitiesArr[1]);
                    $form->setDefault('row3', $quantitiesArr[2]);
                }
            }
        }
        
        // Срок на валидност по подразбиране
        $form->setDefault('validFor', sales_Setup::get('DEFAULT_VALIDITY_OF_QUOTATION'));

        // Дефолтната ценова политика се показва като плейсхолдър
        if($listId = price_ListToCustomers::getListForCustomer($form->rec->contragentClassId, $form->rec->contragentId)){
            $form->setField("priceListId", "placeholder=" . price_Lists::getTitleById($listId));
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if ($rec->state == 'active') {
            if ($mvc->haveRightFor('salefromquotation', (object) array('folderId' => $rec->folderId, 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId))) {
                $items = $mvc->getItems($rec->id);
                
                // Ако има поне един опционален артикул или има варианти на задължителните, бутона сочи към екшън за определяне на количествата
                if (sales_QuotationsDetails::fetch("#quotationId = {$rec->id} AND #optional = 'yes'") || !$items) {
                    $data->toolbar->addBtn('Продажба', array($mvc, 'FilterProductsForSale', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
                
                // Иначе, към създаването на нова продажба
                } else {
                    $warning = '';
                    $title = 'Прехвърляне на артикулите в съществуваща продажба чернова';
                    if (!sales_Sales::count("#state = 'draft' AND #contragentId = {$rec->contragentId} AND #contragentClassId = {$rec->contragentClassId}")) {
                        $warning = 'Сигурни ли сте, че искате да създадете продажба?';
                        $title = 'Създаване на продажба от офертата';
                        $efIcon = 'img/16/star_2.png';
                    } else {
                        $efIcon = 'img/16/cart_go.png';
                    }
                    
                    $data->toolbar->addBtn('Продажба', array($mvc, 'CreateSale', $rec->id, 'ret_url' => true), array('warning' => $warning), "ef_icon={$efIcon},title={$title}");
                }
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if ($data->sales_QuotationsDetails->summary) {
            $data->row = (object) ((array) $data->row + (array) $data->sales_QuotationsDetails->summary);
        }
        
        $dData = $data->sales_QuotationsDetails;
        if ($dData->countNotOptional && $dData->notOptionalHaveOneQuantity) {
            core_Lg::push($data->rec->tplLang);
            $keys = array_keys($dData->rows);
            $firstProductRow = $dData->rows[$keys[0]][0];
            
            if ($firstProductRow->tolerance) {
                $data->row->others .= '<li>' . tr('Толеранс к-во') .": {$firstProductRow->tolerance}</li>";
            }
            
            if (isset($firstProductRow->term)) {
                $data->row->others .= '<li>' . tr('Срок за д-ка') .": {$firstProductRow->term}</li>";
            }
            
            if (isset($firstProductRow->weight)) {
                $data->row->others .= '<li>' . tr('Транспортно тегло') .": {$firstProductRow->weight}</li>";
            }
            core_Lg::pop();
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Ако има проверка на к-та от запитването
            $errorFields2 = $errorFields = $allQuantities = array();
            $checArr = array('1' => $rec->row1, '2' => $rec->row2, '3' => $rec->row3);
            foreach ($checArr as $k => $v) {
                if (!empty($v)) {
                    $parts = type_ComplexType::getParts($v);
                    $rec->{"quantity{$k}"} = $parts['left'];
                    $rec->{"price{$k}"} = ($parts['right'] === '') ? null : $parts['right'];
                    
                    if ($moq = cat_Products::getMoq($rec->productId)) {
                        if (!empty($rec->{"quantity{$k}"}) && $rec->{"quantity{$k}"} < $moq) {
                            $errorFields2[] = "row{$k}";
                        }
                    }
                    
                    if (in_array($parts['left'], $allQuantities)) {
                        $errorFields[] = "row{$k}";
                    } else {
                        $allQuantities[] = $parts['left'];
                    }
                }
            }
            
            // Ако има повтарящи се полета
            if (countR($errorFields)) {
                $form->setError($errorFields, 'Количествата трябва да са различни');
            } elseif (countR($errorFields2)) {
                $moq = core_Type::getByName('double(smartRound)')->toVerbal($moq);
                $form->setError($errorFields2, "Минимално количество за поръчка|* <b>{$moq}</b>");
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->originId)) {
            
            // Намиране на ориджина
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin && cls::haveInterface('cat_ProductAccRegIntf', $origin->instance)) {
                $originRec = $origin->fetch('id,measureId');
                $vat = cat_Products::getVat($origin->that, $rec->date);
                
                // Ако в река има 1 от 3 к-ва
                foreach (range(1, 3) as $i) {
                    
                    // Ако има дефолтно количество
                    $quantity = $rec->{"quantity{$i}"};
                    $price = $rec->{"price{$i}"};
                    if (!$quantity) {
                        continue;
                    }
                    
                    // Прави се опит за добавянето на артикула към реда
                    try {
                        if (!empty($price)) {
                            $price = deals_Helper::getPurePrice($price, $vat, $rec->currencyRate, $rec->chargeVat);
                        }
                        sales_Quotations::addRow($rec->id, $originRec->id, $quantity, $originRec->measureId, $price);
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        
                        if (haveRole('debug')) {
                            $dump = $e->getDump();
                            core_Statuses::newStatus($dump[0], 'warning');
                        }
                    }
                }
                
                // Споделяме текущия потребител със нишката на заданието
                $cu = core_Users::getCurrent();
                doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
            }
        }
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());
        
        if (empty($rec->date)) {
            $row->date = ht::createHint('', 'Датата ще бъде записана при активиране');
        }
        
        if ($fields['-single']) {

            // Линк към от коя оферта е клонирано
            if(isset($rec->clonedFromId)){
                $row->clonedFromId = "#" . self::getHandle($rec->clonedFromId);
                if(!Mode::isReadOnly()){
                    $row->clonedFromId = ht::createLink($row->clonedFromId, self::getSingleUrlArray($rec->clonedFromId));
                }
            }

            // Показване на допълнителните условия от артикулите
            $additionalConditions = deals_Helper::getConditionsFromProducts($mvc->mainDetail, $mvc, $rec->id, $rec->tplLang);
            if (is_array($additionalConditions)) {
                foreach ($additionalConditions as $cond) {
                    $row->others .= "<li>{$cond}</li>";
                }
            }

            if ($cond = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'commonConditionSale')) {
                $row->commonConditionQuote = cls::get('type_Url')->toVerbal($cond);
            }
            
            $items = $mvc->getItems($rec->id, true, true);
            
            if (is_array($items)) {
                $row->transportCurrencyId = $row->currencyId;
                
                $hiddenTransportCost = sales_TransportValues::calcInDocument($mvc, $rec->id);
                $expectedTransportCost = $mvc->getExpectedTransportCost($rec);
                $visibleTransportCost = $mvc->getVisibleTransportCost($rec);
                
                $leftTransportCost = 0;
                sales_TransportValues::getVerbalTransportCost($row, $leftTransportCost, $hiddenTransportCost, $expectedTransportCost, $visibleTransportCost, $rec->currencyRate);
                
                // Ако има транспорт за начисляване
                if ($leftTransportCost > 0) {
                    
                    // Ако може да се добавят артикули в офертата
                    if (sales_QuotationsDetails::haveRightFor('add', (object) array('quotationId' => $rec->id))) {
                        
                        // Добавяне на линк, за добавяне на артикул 'транспорт' със цена зададената сума
                        $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                        $packPrice = $leftTransportCost * $rec->currencyRate;
                        
                        $url = array('sales_QuotationsDetails', 'add', 'quotationId' => $rec->id, 'productId' => $transportId, 'packPrice' => $packPrice, 'optional' => 'no','ret_url' => true);
                        $link = ht::createLink('Добавяне', $url, false, array('ef_icon' => 'img/16/lorry_go.png', 'style' => 'font-weight:normal;font-size: 0.8em', 'title' => 'Добавяне на допълнителен транспорт'));
                        $row->btnTransport = $link->getContent();
                    }
                }
            }
            
            if (isset($rec->deliveryTermId)) {
                $locationId = (!empty($rec->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id') : null;
                if (sales_TransportValues::getDeliveryTermError($rec->deliveryTermId, $rec->deliveryAdress, $rec->contragentClassId, $rec->contragentId, $locationId)) {
                   $row->deliveryError = tr('За транспортните разходи, моля свържете се с представител на фирмата');
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Колко е сумата на очаквания транспорт.
     * Изчислява се само ако няма вариации в задължителните артикули
     *
     * @param stdClass $rec - запис на ред
     *
     * @return float $expectedTransport - очаквания транспорт без ддс в основна валута
     */
    private function getExpectedTransportCost($rec)
    {
        if(isset($rec->expectedTransportCost)) return $rec->expectedTransportCost;
        
        $expectedTransport = 0;
        
        // Ако няма калкулатор в условието на доставка, не се изчислява нищо
        $TransportCalc = cond_DeliveryTerms::getTransportCalculator($rec->deliveryTermId);
        if (!is_object($TransportCalc)) {
            
            return $expectedTransport;
        }
        
        // Подготовка на заявката, взимат се само задължителните складируеми артикули
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$rec->id}");
        $query->where("#optional = 'no'");
        $query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $query->where("#canStore = 'yes'");
        
        $products = $query->fetchAll();
        
        $locationId = null;
        if (isset($rec->deliveryPlaceId)) {
            $locationId = crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$rec->contragentClassId}' AND #contragentId = '{$rec->contragentId}'", $rec->deliveryPlaceId), 'id');
        }
        $codeAndCountryArr = sales_TransportValues::getCodeAndCountryId($rec->contragentClassId, $rec->contragentId, $rec->pCode, $rec->contragentCountryId, $locationId ? $locationId : $rec->deliveryAdress);
        
        $ourCompany = crm_Companies::fetchOurCompany();
        $params = array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode'], 'fromCountry' => $ourCompany->country, 'fromPostalCode' => $ourCompany->pCode);
        
        // Изчисляване на общото тегло на офертата
        $total = sales_TransportValues::getTotalWeightAndVolume($TransportCalc, $products, $rec->deliveryTermId, $params);
        if($total == cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT) return cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT;
        
        // За всеки артикул се изчислява очаквания му транспорт
        foreach ($products as $p2) {
            $fee = sales_TransportValues::getTransportCost($rec->deliveryTermId, $p2->productId, $p2->packagingId, $p2->quantity, $total, $params);
            
            // Сумира се, ако е изчислен
            if (is_array($fee) && $fee['totalFee'] > 0) {
                $expectedTransport += $fee['totalFee'];
            }
        }
        
        // Кеширане на очаквания транспорт при нужда
        if(is_null($rec->expectedTransportCost) && in_array($rec->state, array('active', 'closed'))){
            $rec->expectedTransportCost = $expectedTransport;
            $this->save_($rec, 'expectedTransportCost');
        }
        
        // Връщане на очаквания транспорт
        return $expectedTransport;
    }
    
    
    /**
     * Колко е видимия транспорт начислен в сделката
     *
     * @param stdClass $rec - запис на ред
     *
     * @return float - сумата на видимия транспорт в основна валута без ДДС
     */
    private function getVisibleTransportCost($rec)
    {
        // Извличат се всички детайли и се изчислява сумата на транспорта, ако има
        $query = sales_QuotationsDetails::getQuery();
        $query->where("#quotationId = {$rec->id}");
        $query->where("#optional = 'no'");
        
        return sales_TransportValues::getVisibleTransportCost($query);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $hasTransport = !empty($data->row->hiddenTransportCost) || !empty($data->row->expectedTransportCost) || !empty($data->row->visibleTransportCost);
        
        $isReadOnlyMode = Mode::isReadOnly();
        
        if ($isReadOnlyMode) {
            $tpl->removeBlock('header');
        }
        
        if ($hasTransport === false || $isReadOnlyMode || core_Users::haveRole('partner')) {
            $tpl->removeBlock('TRANSPORT_BAR');
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
         // Може да се създава към артикул само ако артикула е продаваем
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('cat_Products')) {
                $canSell = $origin->fetchField('canSell');
                if ($canSell == 'no') {
                    $res = 'no_one';
                }
            }
        }
        
        if ($action == 'salefromquotation') {
            $sRec = isset($rec->folderId) ? (object)array('folderId' => $rec->folderId) : null;
            $res = sales_Sales::getRequiredRoles('add', $sRec, $userId);

            if(isset($rec)){
                if($res != 'no_one'){

                    // Ако има разминаване между контрагента в офертата и данните от папката, забранява се създаване на продажба
                    $folderCover = doc_Folders::getCover($rec->folderId);
                    if($folderCover->that != $rec->contragentId || $folderCover->getClassId() != $rec->contragentClassId){
                        $res = 'no_one';
                    }
                }
            }
        }
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
        $rec = $this->fetchRec($id);
        $handle = $this->getHandle($id);
        $tpl = new core_ET(tr("Моля запознайте се с нашата оферта|* : #[#handle#]."));
        $tpl->append($handle, 'handle');
        
        if($rec->chargeVat == 'separate'){
            $tpl->append("\n\n" . tr("Обърнете внимание, че цените в тази оферта са [b]без ДДС[/b]. В договора ДДС ще е на отделен ред."));
        }
        
        return $tpl->getContent();
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова  артикула, активираме и нея
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $updateFields = array();
        
        if (!isset($rec->contragentId)) {
            $rec = self::fetch($rec->id);
        }
        
        // Ако няма дата попълваме текущата след активиране
        if (empty($rec->date)) {
            $updateFields[] = 'date';
            $rec->date = dt::today();
        }
        
        if (empty($rec->deliveryTime) && empty($rec->deliveryTermTime)) {
            $rec->deliveryTermTime = $mvc->getMaxDeliveryTime($rec->id);
            if (isset($rec->deliveryTermTime)) {
                $updateFields[] = 'deliveryTermTime';
            }
        }
        
        if (countR($updateFields)) {
            $mvc->save($rec, $updateFields);
        }
        
        // Ако запитването е в папка на контрагент вкарва се в група запитвания
        $clientGroupId = crm_Groups::getIdFromSysId('customers');
        $groupRec = (object)array('name' => 'Оферти', 'sysId' => 'quotationsClients', 'parentId' => $clientGroupId);
        $groupId = crm_Groups::forceGroup($groupRec);
        
        cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, false);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Оферта нормален изглед', 'content' => 'sales/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg', 'narrowContent' => 'sales/tpl/QuotationHeaderNormalNarrow.shtml');
        $tplArr[] = array('name' => 'Оферта изглед за писмо', 'content' => 'sales/tpl/QuotationHeaderLetter.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Quotation', 'content' => 'sales/tpl/QuotationHeaderNormalEng.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/QuotationHeaderNormalEngNarrow.shtml');
        $res = doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }


    /**
     * Екшън генериращ продажба от оферта
     */
    public function act_CreateSale()
    {
        $this->requireRightFor('salefromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        expect($rec->state = 'active');
        expect($items = $this->getItems($id));
        $this->requireRightFor('salefromquotation', $rec);
        $force = Request::get('force', 'int');
        
        // Ако не форсираме нова продажба
        if (!$force && !core_Users::isContractor()) {
            // Опитваме се да намерим съществуваща чернова продажба
            if (!Request::get('dealId', 'key(mvc=sales_Sales)') && !Request::get('stop')) {
                
                return new Redirect(array('sales_Sales', 'ChooseDraft', 'contragentClassId' => $rec->contragentClassId, 'contragentId' => $rec->contragentId, 'ret_url' => true, 'quotationId' => $rec->id));
            }
        }
        
        // Ако няма създава се нова продажба
        if (!$sId = Request::get('dealId', 'key(mvc=sales_Sales)')) {
            try{
                $sId = $this->createDeal($rec);
                sales_Sales::logWrite('Създаване от оферта', $sId);
            } catch(core_exception_Expect $e){
                reportException($e);
                $this->logErr($e->dump[0], $rec->id);
                followRetUrl(null, "Проблем при създаване на продажба от оферта", 'error');
            }
        }
        
        // За всеки детайл на офертата подаваме го като детайл на продажбата
        foreach ($items as $item) {
            $addedRecId = sales_Sales::addRow($sId, $item->productId, $item->packQuantity, $item->price, $item->packagingId, $item->discount, $item->tolerance, $item->term, $item->notes);
            
            // Копира се и транспорта, ако има
            $cRec = sales_TransportValues::get($this, $item->quotationId, $item->id);
            if (isset($cRec)) {
                sales_TransportValues::sync('sales_Sales', $sId, $addedRecId, $cRec->fee, $cRec->deliveryTime);
            }
        }
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logWrite('Създаване на продажба от оферта', $id);
        
        // Редирект към новата продажба
        return new Redirect(array('sales_Sales', 'single', $sId), '|Успешно е създадена продажба от офертата');
    }
    
    
    /**
     * Екшън за създаване на заявка от оферта
     */
    public function act_FilterProductsForSale()
    {
        $this->requireRightFor('salefromquotation');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        expect($rec->state == 'active');
        $this->requireRightFor('salefromquotation', $rec);
        
        // Подготовка на формата за филтриране на данните
        $form = $this->getFilterForm($rec->id, $id);
        $form->input();
        
        if ($form->isSubmitted()) {
            $products = (array) $form->rec;
            
            $setError = true;
            $errFields = array();
            foreach ($products as $index1 => $quantity1) {
                if (!empty($quantity1)) {
                    $setError = false;
                } else {
                    $errFields[] = $index1;
                }
            }
            
            if ($setError === true) {
                $form->setError(implode(',', $errFields), 'Не са зададени количества');
            }
            
            if (!$form->gotErrors()) {
                try{
                    $errorMsg = 'Проблем при създаването на оферта';
                    $sId = $this->createDeal($rec);
                } catch(core_exception_Expect $e){
                    $errorMsg = $e->getMessage();
                    reportException($e);
                    $this->logErr($errorMsg, $rec->id);
                }
                
                if(empty($sId)){
                    followRetUrl(null, $errorMsg, 'error');
                }
                
                foreach ($products as $dRecId) {
                    if(empty($dRecId)) continue;
                    
                    $dRec = sales_QuotationsDetails::fetch($dRecId);
                    
                    // Копира се и транспорта, ако има
                    $addedRecId = sales_Sales::addRow($sId, $dRec->productId, $dRec->packQuantity, $dRec->price, $dRec->packagingId, $dRec->discount, $dRec->tolerance, $dRec->term, $dRec->notes);
                    $tRec = sales_TransportValues::get($this, $id, $dRecId);
                    
                    if (isset($tRec->fee)) {
                        sales_TransportValues::sync('sales_Sales', $sId, $addedRecId, $tRec->fee, $tRec->deliveryTime, $tRec->explain);
                    }
                }
                
                // Редирект към сингъла на новосъздадената продажба
                return new Redirect(array('sales_Sales', 'single', $sId));
            }
        }
        
        if (core_Users::haveRole('partner')) {
            plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
        }
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     *
     * @param int $id - ид на записа
     *
     * @return core_Form - готовата форма
     */
    private function getFilterForm($id)
    {
        $form = cls::get('core_Form');
        
        $form->title = 'Създаване на продажба от|* ' . sales_Quotations::getFormTitleLink($id);
        $form->info = tr('Моля уточнете, кои редове ще се прехвърлят в продажбата');
        $filteredProducts = $this->filterProducts($id);
        
        foreach ($filteredProducts as $index => $product) {
            if ($product->optional == 'yes') {
                $product->title = "Опционални->{$product->title}";
                $product->options = array('' => '') + $product->options;
                $mandatory = '';
            } else {
                $product->title = "Оферирани->{$product->title}";
                $mandatory = '';
                if (countR($product->options) > 1) {
                    $product->options = array('' => '') + $product->options;
                    $mandatory = 'mandatory';
                }
            }
            $form->FNC($index, 'double(decimals=2)', "input,caption={$product->title},hint={$product->hint},{$mandatory}");
            if (countR($product->options) == 1) {
                $default = key($product->options);
            }
            
            $product->options = $product->options + array('0' => '0');
            $form->setOptions($index, $product->options);
            $form->setDefault($index, $default);
        }
        
        $form->toolbar->addSbBtn('Създаване', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title = Прекратяване на действията');
        
        return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     *
     * @param int $id - ид на оферта
     *
     * @return array $products - филтрираните продукти
     */
    private function filterProducts($id)
    {
        $Detail = clone cls::get('sales_QuotationsDetails');
        
        $rec = $this->fetchRec($id);
        $products = array();
        $query = $Detail->getQuery();
        $query->where("#quotationId = {$id}");
        $query->orderBy('optional=ASC,id=ASC');
        $dRecs = $query->fetchAll();
        
        deals_Helper::fillRecs($Detail, $dRecs, $rec);
        
        foreach ($dRecs as $dRec) {
            $index = "{$dRec->productId}|{$dRec->optional}|{$dRec->packagingId}|" .md5($dRec->notes);
            
            if (!array_key_exists($index, $products)) {
                $title = cat_Products::getTitleById($dRec->productId);
                $title = str_replace(',', '.', $title);
                if (isset($dRec->packagingId)) {
                    $title .= ' / ' . cat_UoM::getShortName($dRec->packagingId);
                }
                
                $hint = null;
                if (!empty($dRec->notes)) {
                    $title .= ' / ' . str::limitLen(strip_tags(core_Type::getByName('richtext')->toVerbal($dRec->notes)), 10);
                    $hint = $dRec->notes;
                }
                $products[$index] = (object) array('title' => $title, 'options' => array(), 'optional' => $dRec->optional, 'suggestions' => false, 'hint' => $hint);
            }
            
            if ($dRec->optional == 'yes') {
                $products[$index]->suggestions = true;
            }
            
            if ($dRec->quantity) {
                core_Mode::push('text', 'plain');
                $packQuantity = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packQuantity);
                $packPrice = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packPrice);
                
                $val = "{$packQuantity} / {$packPrice} " . $rec->currencyId;
                foreach (array('discount', 'tolerance', 'term') as $fld){
                    if(!empty($dRec->{$fld})){
                        $Type = ($fld != 'term') ? core_Type::getByName('percent') : core_Type::getByName('time');
                        $val .= " / " . $Type->toVerbal($dRec->{$fld});
                    }
                }
                core_Mode::pop('text');
                
                $products[$index]->options[$dRec->id] = $val;
            }
        }
        
        return $products;
    }
    
    
    /**
     * Затваряне на изтекли оферти по крон
     */
    public function cron_CloseQuotations()
    {
        $today = dt::today();
        
        // Селектираме тези фактури, с изтекла валидност
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        $query->where('#validFor IS NOT NULL');
        $query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
        $query->where("#expireOn < '{$today}'");
        $query->show('id');
        
        // Затварят се
        while ($rec = $query->fetch()) {
            try {
                $rec->state = 'closed';
                $this->save_($rec, 'state');
                $this->logWrite('Затваряне на изтекла оферта', $rec->id);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        $quotationId = $res->id;
        $rec = $mvc->fetch($quotationId);
        
        $error = array();
        $saveRecs = array();
        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where("#quotationId = {$quotationId}");
        $dQuery->where('#price IS NULL || #tolerance IS NULL || #term IS NULL || #weight IS NULL');
        while ($dRec = $dQuery->fetch()) {
            if (!isset($dRec->price)) {
                sales_QuotationsDetails::calcLivePrice($dRec, $rec, true);
                
                if (!isset($dRec->price)) {
                    $error[] = cat_Products::getTitleById($dRec->productId);
                }
            }
            
            if (!isset($dRec->term)) {
                if ($term = cat_Products::getDeliveryTime($dRec->productId, $dRec->quantity)) {
                    if ($deliveryTime = sales_TransportValues::get('sales_Quotations', $dRec->quotationId, $dRec->id)->deliveryTime) {
                        $term += $deliveryTime;
                    }
                    $dRec->term = $term;
                }
            }
            
            if (!isset($dRec->tolerance)) {
                if ($tolerance = cat_Products::getTolerance($dRec->productId, $dRec->quantity)) {
                    $dRec->tolerance = $tolerance;
                }
            }
            
            if (!isset($dRec->weight)) {
                $dRec->weight = cat_Products::getTransportWeight($dRec->productId, $dRec->quantity);
            }
            
            $saveRecs[] = $dRec;
        }
        
        if (countR($error)) {
            $imploded = implode(', ', $error);
            $start = (countR($error) == 1) ? 'артикулът' : 'артикулите';
            $mid = (countR($error) == 1) ? 'му' : 'им';
            $msg = "На {$start}|* <b>{$imploded}</b> |трябва да {$mid} се въведе цена|*";
            
            core_Statuses::newStatus($msg, 'error');
            
            return false;
        }
        
        // Ако има избрано условие на доставка, пзоволява ли да бъде контиран документа
        if(isset($rec->deliveryTermId)){
            $error = null;
            if(!cond_DeliveryTerms::checkDeliveryDataOnActivation($rec->deliveryTermId, $rec, $rec->deliveryData, $mvc, $error)){
                core_Statuses::newStatus($error, 'error');
                
                return false;
            }
        }

        $errorMsg = null;
        if(deals_Helper::hasProductsBellowMinPrice($mvc, $rec, $errorMsg)){
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }

        cls::get('sales_QuotationsDetails')->saveArray($saveRecs);
    }
    
    
    /**
     * Връща заглавието на имейла
     *
     * @param int  $id
     * @param bool $isForwarding
     *
     * @return string
     *
     * @see email_DocumentIntf
     */
    public function getDefaultEmailSubject($id, $isForwarding = false)
    {
        $res = '';
        
        if (!$id) {
            
            return $res;
        }
        $rec = $this->fetch($id);
        
        if (!$rec) {
            
            return $res;
        }
        
        $res = '';
        
        if ($rec->reff) {
            $res = $rec->reff . ' ';
        }
        
        
        $dQuery = sales_QuotationsDetails::getQuery();
        $dQuery->where(array("#quotationId = '[#1#]'", $id));
        
        // Показваме кода на продукта с най високата сума
        $maxAmount = null;
        $productId = 0;
        $pCnt = 0;
        while ($dRec = $dQuery->fetch()) {
            $amount = $dRec->price * $dRec->quantity;
            
            if ($dRec->discount) {
                $amount = $amount * (1 - $dRec->discount);
            }
            
            if (!isset($maxAmount) || ($amount > $maxAmount)) {
                $maxAmount = $amount;
                $productId = $dRec->productId;
            }
            
            $pCnt++;
        }
        
        $pCnt--;
        if ($productId) {
            $res .= cat_products::getTitleById($productId);
            
            if ($pCnt > 0) {
                $res .= ' ' . tr('и още') . '...';
            }
        }
        
        return $res;
    }

    
    /**
     * Екшън за автоматичен редирект към създаване на детайл
     */
    function act_autoCreateInFolder()
    {
        $this->requireRightFor('add');
        expect($folderId = Request::get('folderId', 'int'));
        $this->requireRightFor('add', (object)array('folderId' => $folderId));
        expect(doc_Folders::haveRightToFolder($folderId));
        
        // Има ли избрана константа
        $constValue = sales_Setup::get('NEW_QUOTATION_AUTO_ACTION_BTN');
        if($constValue == 'form') {
            
            return Redirect(array($this, 'add', 'folderId' => $folderId, 'ret_url' => getRetUrl()));
        }
        
        // Генерира дефолтите според папката
        $Cover = doc_Folders::getCover($folderId);
        $fields = array();
        $fieldsWithStrategy = array_keys(static::$defaultStrategies);
        foreach ($fieldsWithStrategy as $field){
            $fields[$field] = cond_plg_DefaultValues::getDefaultValue($this, $folderId, $field);
        }
        
        // Създаване на мастър на документа
        try{
            $masterId = static::createNewDraft($Cover->getClassId(), $Cover->that, null, $fields);
            if(isset($productId)){
                static::logWrite('Създаване от артикул', $masterId);
            } else {
                static::logWrite('Създаване', $masterId);
            }
        } catch(core_exception_Expect $e){
            reportException($e);
            
            followRetUrl(null, "Проблем при създаване на оферта");
        }
        
        $redirectUrl = array($this, 'single', $masterId);
        $Detail = cls::get($this->mainDetail);
        
        // Редирект към добавянето на детайл
        if($constValue == 'addProduct') {
            if($Detail->haveRightFor('add', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'add', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        } elseif($constValue == 'createProduct'){
            if($Detail->haveRightFor('createproduct', (object)array("{$Detail->masterKey}" => $masterId))){
                $redirectUrl = array($Detail, 'createproduct', "{$Detail->masterKey}" => $masterId, 'optional' => 'no', 'ret_url' => array($this, 'single', $masterId));
            }
        }
        
        return Redirect($redirectUrl);
    }
}
