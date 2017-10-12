<?php


/**
 * Мениджър за свързани документи и файлове
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Linked extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Свързани документи и файлове";
    
    
    /**
     * Сингъл заглавие
     */
    public $singleTitle = "Свързани документи и файлове";
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
	
	
    /**
     * Кой има право да оттегле?
     */
    public $canReject = 'powerUser';
	
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_Wrapper, plg_Created, plg_State, plg_Rejected, plg_RowTools2, plg_Modified';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('outType', 'enum(doc=Документ,file=Файл)', 'caption=Изходен->Тип');
        $this->FLD('outVal', 'int', 'caption=Изходен->Стойност');
        $this->FLD('inType', 'enum(doc=Документ,file=Файл)', 'caption=Входящ->Тип');
        $this->FLD('inVal', 'int', 'caption=Входящ->Стойност');
        $this->FLD('comment', 'varchar', 'caption=Пояснения');
        $this->FLD('actType', 'varchar(16)', 'caption=Действие, input=none');
        $this->FLD('state', 'enum(active=Активно, rejected=Оттеглено)', 'caption=Състояние, input=none');
        
        $this->setDbUnique('outType, outVal, inType, inVal');
    }
    
    
    /**
     * Връща всички записи за подадените типове
     * 
     * @param string $type
     * @param integer $id
     * @param boolean $showRejecte
     * @param number $limit
     * 
     * @return array
     */
    public static function getRecsForType($type, $id, $showRejecte = TRUE, $limit = 1000)
    {
        $query = self::getQuery();
        
        if (!$showRejecte) {
            $query->where("#state != 'rejected'");
        }
        
        $query->limit($limit);
        
        $query->where(array("#outType = '[#1#]' AND #outVal = '[#2#]'", $type, $id));
        $query->orWhere(array("#inType = '[#1#]' AND #inVal = '[#2#]'", $type, $id));
        
        $query->orderBy('createdOn', 'DESC');
        
        $recArr = $query->fetchAll();
        
        return $recArr;
    }
    
    
    /**
     * Връща вербализирани данни за различни преставяния
     * 
     * @param string $type
     * @param integer $val
     * @param string $viewType
     * @param boolean $showRejecte
     * @param integer $limit
     * 
     * @return NULL|string|core_ET
     */
    public static function getListView($type, $val, $viewType = 'table', $showRejecte = TRUE, $limit = 1000)
    {
        $recArr = self::getRecsForType($type, $val, $showRejecte, $limit);
        
        $rowArr = array();
        
        $me = cls::get(get_called_class());
        
        foreach ($recArr as $id => $rec) {
            
            $comment = $me->getVerbal($rec, 'comment');
            
            $getUrlWithAccess = FALSE;
            
            if ($rec->state == 'active') {
                $getUrlWithAccess = TRUE;
            }
            
            if ($rec->outType == $type && $rec->outVal == $val) {
                $icon = 'img/16/arrow_right.png';
                $rowArr[$id]['docLink'] = self::getVerbalLinkForType($rec->inType, $rec->inVal, $comment, $getUrlWithAccess);
            } else {
                $icon = 'img/16/arrow_left.png';
                $rowArr[$id]['docLink'] = self::getVerbalLinkForType($rec->outType, $rec->outVal, $comment, $getUrlWithAccess);
            }
            
            $rowArr[$id]['comment'] = $comment;
            
            $rowArr[$id]['icon'] = ht::createElement("img", array("src" => sbf($icon, '', Mode::isReadOnly())));
            $rowArr[$id]['docLink'] = $rowArr[$id]['icon'] . $rowArr[$id]['docLink'];
            
            if ($row = $me->recToVerbal($rec)) {
                if ($row->_rowTools instanceOf core_RowToolbar) {
                    $rowArr[$id]['_rowTools'] = $row->_rowTools->renderHtml();
                }
                
                $rowArr[$id]['ROW_ATTR'] = $row->ROW_ATTR;
            }
        }
        
        if (empty($rowArr)) return ;
        
        if ($viewType == 'table') {
            $table = cls::get('core_TableView');
            
            $res = $table->get($rowArr, "_rowTools=✍,
                                          docLink=Връзка,
	                                      comment=Коментар");
        } else {
            $res = '';
            foreach ($rowArr as $row) {
                $res .= $res ? "\n" : '';
                $res .= $row['docLink'];
                if (trim($row['comment'])) {
                    $res .= ' (' . trim($row['comment']) . ')';
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за свързване на файлове и документи
     * 
     * @return Redirect|core_Et
     */
    function act_Link()
    {
        $pArr = array('inType', 'foreignId');
        Request::setProtected($pArr);
        
        $type = Request::get('inType');
        
        $originFId = $fId = Request::get('foreignId', 'int');
        
        Request::removeProtected($pArr);
        
        expect($type && $fId);
        
        if ($type == 'doc') {
            $docInst = doc_Containers::getDocument($fId);
            expect($docInst->instance);
            
            $clsInst = $docInst->instance;
            $fId = $docInst->that;
            
            $clsInst->requireRightFor('single', $fId);
            
            $rec = $clsInst->fetch($fId);
            
            expect($fId);
        } elseif ($type == 'file') {
            $clsInst = cls::get('fileman_Files');
            $clsInst->requireRightFor('single', $fId);
            $rec = $clsInst->fetch($fId);
            
            $fId = fileman::idToFh($fId);
        } else {
            expect(FALSE, $type);
        }
        
        expect($rec);
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($clsInst, 'single', $fId);
        }
        
        $form = cls::get('core_Form');
        
        // @todo intf
        
        // Вид връзка
        $actTypeArr = array('' => '', 'linkDoc' => 'Връзка с документ', 'linkFile' => 'Връзка с файл', 'newDoc' => 'Нов документ');
        $enumInst = cls::get('type_Enum');
        $enumInst->options = $actTypeArr;
        $form->FNC('act', $enumInst, 'caption=Действие, input, removeAndRefreshForm=linkContainerId|linkFolderId|linkThreadId|linkDocType, mandatory, silent');
        
        $defAct = $this->getDefaultActionFor($originFId, $type);
        if ($defAct) {
            $form->setDefault('act', $defAct);
        }
        
        $form->input();
        
        $act = trim($form->rec->act);
        
        if ($act == 'linkDoc') {
            $form->FNC('linkDocType', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид, input, removeAndRefreshForm=linkContainerId|linkFolderId');
            $form->input();
            
            $form->FNC('linkFolderId', 'key2(mvc=doc_Folders, titleFld=title, maxSuggestions=100, selectSourceArr=doc_Linked::prepareFoldersForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ', showWithDocs)', 'caption=Папка, input, removeAndRefreshForm=linkContainerId');
            $form->input();
            
            $form->FNC('linkContainerId', 'key2(mvc=doc_Containers, titleFld=id, maxSuggestions=100, selectSourceArr=doc_Linked::prepareLinkDocId, allowEmpty, docType=' . $form->rec->linkDocType . ', folderId=' . $form->rec->linkFolderId . ')', 'caption=Документ, input, mandatory, refreshForm');
        } elseif ($act == 'linkFile') {
            $form->FNC('linkFileId', 'fileman_FileType(bucket=Linked)', 'caption=Файл, input, mandatory');
        } elseif ($act == 'newDoc') {
            $form->FNC('linkDocType', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид, input, mandatory, removeAndRefreshForm=linkFolderId|linkThreadId');
            
            // Махаме документите, които не можем да създадем
            $optArr = $form->fields['linkDocType']->type->prepareOptions();
            foreach ($optArr as $optClsId => $title) {
                $optClsInst = cls::get($optClsId);
                
                if (!$optClsInst->haveRightFor('add')) {
                    
                    unset($optArr[$optClsId]);
                }
            }
            $form->fields['linkDocType']->type->options = $optArr;
            
            $form->input();
            
            if ($form->rec->linkDocType) {
                $form->FNC('linkFolderId', 'key2(mvc=doc_Folders, titleFld=title, maxSuggestions=100, selectSourceArr=doc_Linked::prepareFoldersForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ')', 'caption=Папка, input, mandatory, removeAndRefreshForm=linkThreadId');
                $form->input();
                
                $dInst = cls::get($form->rec->linkDocType);
                
                // Ако документа може да се създаде в съществуваща нишка, показваме избор
                if ($form->rec->linkFolderId && !$dInst->onlyFirstInThread) {
                    
                    $mandatory = '';
                    
                    if (!$dInst->canAddToFolder($form->rec->linkFolderId) || !$dInst->haveRightFor('add', (object) array('folderId' => $form->rec->linkFolderId))) {
                        $mandatory = ' ,mandatory';
                    }
                    
                    $form->FNC('linkThreadId', 'key2(mvc=doc_Threads, titleFld=firstContainerId, maxSuggestions=100, selectSourceArr=doc_Linked::prepareThreadsForDoc, allowEmpty, docType=' . $form->rec->linkDocType . ', folderId=' . $form->rec->linkFolderId . ')', "caption=Нишка, input, refreshForm{$mandatory}");
                }
            }
        } else {
            // @todo intf
        }
        
        $form->FNC('comment', 'varchar', 'caption=Пояснение, input');
        
        $form->input();
        
        if ($form->isSubmitted()) {
            
            $retUrl = getRetUrl();
            
            $nRec = new stdClass();
            $nRec->outType = $type;
            $nRec->outVal = $originFId;
            $nRec->comment = $form->rec->comment;
            $nRec->state = 'active';
            $nRec->actType = $form->rec->act;
            
            if ($act == 'linkDoc') {
                $nRec->inType = 'doc';
                $nRec->inVal = $form->rec->linkContainerId;
            } elseif ($act == 'linkFile') {
                $nRec->inType = 'file';
                $nRec->inVal = fileman_Files::fetchByFh($form->rec->linkFileId)->id;
            } elseif ($act == 'newDoc') {
                
                // Ако се създава нов документ, записваме в кеша и след създаване добавяме запис
                
                $nRec->inType = 'doc';
                
                $url = array(cls::get($form->rec->linkDocType), 'add', 'folderId' => $form->rec->linkFolderId);
                
                if ($form->rec->linkThreadId) {
                    $url['threadId'] = $form->rec->linkThreadId;
                }
                
                $url['linkedHashKey'] = 'LHK_' . substr(md5(serialize($nRec) . '|' . dt::now() . '|' . core_Users::getCurrent()), 0, 8);
                
                $url['ret_url'] = TRUE;
                
                core_Permanent::set($url['linkedHashKey'], $nRec, 120);
                
                return new Redirect($url);
            } else {
                // @todo - интерфейс
            }
            
            // Прави необходимите проверки и добавя запис
            $fieldsArr = array();
            if (!$this->isUnique($nRec, $fieldsArr)) {
                $form->setError($fieldsArr, "Вече съществува запис със същите данни");
            } else {
                
                if ($nRec->inVal && ($nRec->inType == $nRec->outType) && ($nRec->inVal == $nRec->outVal)) {
                    $errMsg = 'Избрали сте същия ';
                    if ($nRec->inType == 'doc') {
                        $errMsg .= 'документ';
                    } else {
                        $errMsg .= 'файл';
                    }
                    $form->setError('linkContainerId', $errMsg);
                } else {
                    $this->save($nRec);
                    
                    try {
                        $strType = 'документ';
                        if ($nRec->outType == 'doc') {
                            if ($nRec->inType == 'file') {
                                $strType = 'файл';
                            }
                            $outDoc = doc_Containers::getDocument($nRec->outVal);
                            $outDoc->instance->logRead("Добавена връзка към {$strType}", $outDoc->that);
                        }
                        
                        $strType = 'документ';
                        if ($nRec->inType == 'doc') {
                            if ($nRec->outType == 'file') {
                                $strType = 'файл';
                            }
                            $inDoc = doc_Containers::getDocument($nRec->inVal);
                            $inDoc->instance->logRead("Добавена връзка от {$strType}", $inDoc->that);
                        }
                    } catch (core_exception_Expect $e) { }
                    
                    return new Redirect($retUrl);
                }
            }
        }
        
        // Показва избрания документ, когато ще се прикача към него
        if ($act == 'linkDoc' && $form->rec->linkContainerId) {
            $form->layout = $form->renderLayout();
            $tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Документ") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
            
            $document = doc_Containers::getDocument($form->rec->linkContainerId);
            if ($document->haveRightFor('single')) {
                $docHtml = $document->getInlineDocumentBody();
                
                $tpl->replace($docHtml, 'DOCUMENT');
                
                $form->layout->append($tpl);
            }
        }
        
        $form->title = "Свързване на файлове и документи с|* " . $clsInst->getLinkToSingle($fId);
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Добавяне на връзка');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Връща възможно най-добрият екшън за съответния документ
     * 
     * @param integer $docId
     * @param string $type
     * @param integer|NULL $folderId
     * @param integer|NULL $userId
     * 
     * @return string|mixed
     */
    protected static function getDefaultActionFor($docId, $type, $folderId = NULL, $userId = NULL)
    {
        $qLimit = 3;
        $minBestCnt = $qLimit - 1;
        
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if (!$folderId) {
            if ($type == 'doc') {
                $docInst = doc_Containers::getDocument($docId);
                
                $folderId = $docInst->fetchField('folderId');
            }
        }
        
        $query = self::getQuery();
        $query->state = 'active';
        $query->orderBy('createdOn', 'DESC');
        $query->limit($qLimit);
        
        $query->where(array("#outType = '[#1#]'", $type));
		
        if ($type == 'doc') {
            
            // Подобен файл - от същия клас
            
            $document = doc_Containers::getDocument($docId);
            $docClsId = $document->instance->getClassId();
            
            $query->EXT('cDocClass', 'doc_Containers', 'externalKey=outVal, externalName=docClass');
            $query->where(array("#cDocClass = '[#1#]'", $docClsId));
        } elseif ($type == 'file') {
            $fRec = fileman_Files::fetch($docId);
            $ext = fileman_Files::getExt($fRec->name);
            $query->EXT('fileName', 'fileman_Files', 'externalKey=outVal, externalName=name');
            
            $mimeType = fileman_Mimes::getMimeByExt($ext);
            
            // Подобни файлове - от миме типа
            $extArr = array();
            if ($mimeType) {
                $extArr = fileman_Mimes::getExtByMime($mimeType);
                
                if (!isset($extArr)) {
                    $extArr = array();
                }
            }
            
            if (array_search($ext, $extArr) === FALSE) {
                $extArr[] = $ext;
            }
            
            $or = FALSE;
            foreach ($extArr as $ext) {
                $query->where(array("#fileName LIKE '%.[#1#]'", $ext), $or);
                $or = TRUE;
            }
        }
        
        // Същия тим документ или папка +
        // 1 - потребител и папка
        // 2 - потребител
        // 3 - папка
        
        $qArr = array();
        
        $qArr[2] = clone $query;
        $qArr[2]->where(array("#createdBy = '[#1#]'", $userId));
        
        if ($folderId) {
            
            $qArr[1] = clone $query;
            
            $qArr[1]->EXT('folderId', 'doc_Containers', 'externalKey=outVal, externalName=folderId');
            $qArr[1]->where(array("#folderId = '[#1#]'", $folderId));
            
            $qArr[3] = clone $qArr[1];
            
            $qArr[1]->where(array("#createdBy = '[#1#]'", $userId));
        }
        
        ksort($qArr);
        
        $actStr = '';
        foreach ($qArr as $q) {
            $actTypeArr = array();
            while ($rec = $q->fetch()) {
                
                if (!$rec->actType) continue;
                $actTypeArr[$rec->actType]++;
            }
            
            if (empty($actTypeArr)) continue;
            
            arsort($actTypeArr);
            if (!empty($actTypeArr)) {
                reset($actTypeArr);
                $firstElemKey = key($actTypeArr);
                
                if ($actTypeArr[$firstElemKey] >= $minBestCnt) {
                    $actStr = $firstElemKey;
                    
                    break;
                }
            }
        }
        
        return $actStr;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на документ
     * 
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|array|integer $onlyIds
     * @param boolean $includeHiddens
     * 
     * @return array
     */
    public static function prepareLinkDocId($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        $cInst = cls::get($params['mvc']);
        
        $cQuery = $cInst->getQuery();
        
        doc_Threads::restrictAccess($cQuery);
        
        $cQuery->orderBy('modifiedOn', 'DESC');
        
        setIfNot($limit, $params['maxSuggestions'], 100);
        
        $cQuery->limit($limit);
        
        if(!$includeHiddens) {
            $cQuery->where("#state != 'rejected'");
        }
        
        if(is_array($onlyIds)) {
            if(!count($onlyIds)) {
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $cQuery->where("#id IN ($ids)");
        } elseif(ctype_digit("{$onlyIds}")) {
            $cQuery->where("#id = $onlyIds");
        }
        
        if ($q) {
            plg_Search::applySearch($q, $cQuery, 'searchKeywords');
        }
        
        if ($params['docType']) {
            $cQuery->where(array("#docClass = '[#1#]'", $params['docType']));
        }
        
        if ($params['folderId']) {
            $cQuery->where(array("#folderId = '[#1#]'", $params['folderId']));
        }
        
        $sArr = array();
        while ($cRec = $cQuery->fetchAndCache()) {
            try {
                $dInst = cls::get($cRec->docClass);
                $oRow = $dInst->getDocumentRow($cRec->docId);
                $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                $title = trim($title);
                $title = str::limitLen($title, 35);
                $sArr[$cRec->id] = $title . ' (' . $dInst->getHandle($cRec->docId) . ')';
            } catch (core_exception_Expect $e) {
                reportException($e);
                continue;
            }
        }
        
        return $sArr;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на папка
     *
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|array|integer $onlyIds
     * @param boolean $includeHiddens
     *
     * @return array
     */
    public static function prepareFoldersForDoc($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        if ($params['docType']) {
            $docTypeInst = cls::get($params['docType']);
        }
        
        $query = doc_Folders::getQuery();
        $query->orderBy("last", "DESC");
        
        doc_Folders::restrictAccess($query, NULL, FALSE);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
        }
        
        if(is_array($onlyIds)) {
            if(!count($onlyIds)) {
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN ($ids)");
        } elseif(ctype_digit("{$onlyIds}")) {
            $query->where("#id = $onlyIds");
        }
        
        $titleFld = $params['titleFld'];
        
        $show = "id,class,{$titleFld}";
        
        $query->EXT('class', 'core_Classes', 'externalKey=coverClass,externalName=title');
        
        if ($q) {
            $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
            
            $show .= ',searchFieldXpr';
            
            if ($q{0} == '"') $strict = TRUE;
            
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            foreach($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '\ {1}[^a-z0-9\p{L}]?[#1#]'", $w));
            }
        }
        
        // Ако е зададено да се показват папките в които има такива документи
        if ($params['showWithDocs'] && $docTypeInst) {
            
            $pKey = 'linkedDocFolders_' . substr(md5($docTypeInst->className . '|' . core_Users::getCurrent()), 0, 8);
            
            $cacheTime = 5;
            
            $minCreatedOn = dt::subtractSecs($cacheTime * 60);
            $fArr = core_Permanent::get($pKey, $minCreatedOn);
            
            if (!isset($fArr) || !is_array($fArr)) {
                $dQuery = $docTypeInst->getQuery();
                
                $dQuery->where("#state != 'rejected'");
                
                doc_Folders::restrictAccess($dQuery, NULL, FALSE);
                
                $dQuery->groupBy('folderId');
                
                $dQuery->show('folderId');
                
                $fArr = array();
                while ($dRec = $dQuery->fetch()) {
                    $fArr[$dRec->folderId] = $dRec->folderId;
                }
                
                core_Permanent::set($pKey, $fArr, $cacheTime);
            }
            
            if (!empty($fArr)) {
                $query->in('id', $fArr);
            } else {
                // Да не се показва нищо, ако няма документ
                $query->where("1=2");
            }
        }
        
        $query->show($show);
        
        $res = array();
        
        setIfNot($limit, $params['maxSuggestions'], 100);
        
        while($rec = $query->fetch()) {
            
            if ($docTypeInst) {
                
                if ($docTypeInst->onlyFirstInThread && (!$docTypeInst->canAddToFolder($rec->id) || !$docTypeInst->haveRightFor('add', (object) array('folderId' => $rec->id)))) continue;
            }
            
            if (!$limit--) break;
            
            $title = trim($rec->{$titleFld});
            $title = str::limitLen($title, 35);
            
            $res[$rec->id] = $title . ' (' . $rec->class . ')';
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя опциите за key2 за избор на нишка
     *
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|array|integer $onlyIds
     * @param boolean $includeHiddens
     *
     * @return array
     */
    public static function prepareThreadsForDoc($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        if ($params['docType']) {
            $docTypeInst = cls::get($params['docType']);
        }
        
        $folderId = $params['folderId'];
        
        $query = doc_Threads::getQuery();
        $query->where(array("#folderId = '[#1#]'", $folderId));
        
        $query->orderBy("last", "DESC");
        
        doc_Threads::restrictAccess($query);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected'");
        }
        
        if(is_array($onlyIds)) {
            if(!count($onlyIds)) {
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN ($ids)");
        } elseif(ctype_digit("{$onlyIds}")) {
            $query->where("#id = $onlyIds");
        }
        
        $show = "id, firstDocClass, firstDocId";
        if ($q) {
            $query->EXT('searchKeywords', 'doc_Containers', 'externalKey=firstContainerId, externalName=searchKeywords');
            
            $show .= ' ,searchKeywords';
            
            plg_Search::applySearch($q, $query, 'searchKeywords');
        }
        
        
        $query->show($show);
        
        $res = array();
        
        setIfNot($limit, $params['maxSuggestions'], 100);
        
        while($rec = $query->fetch()) {
            
            if ($docTypeInst) {
                if ($docTypeInst->onlyFirstInThread || !$docTypeInst->canAddToThread($rec->id)) continue;
                
                if (!$docTypeInst->haveRightFor('add', (object) array('threadId' => $rec->id))) continue;
            }
            
            if (!$limit--) break;
            
            $title = $rec->id;
            
            if ($rec->firstDocClass) {
                try {
                    $dInst = cls::get($rec->firstDocClass);
                    
                    $oRow = $dInst->getDocumentRow($rec->firstDocId);
                    $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                    $title = trim($title);
                    $title = str::limitLen($title, 50);
                } catch (core_exception_Expect $e) {
                    // Не се променя title
                }
            }
            
            $res[$rec->id] = $title;
        }
        
        return $res;
    }
    
    
    /**
     * Помощна функция за връщане на линк към документ/файл
     *
     * @param string $type
     * @param integer $valId
     * @param NULL|string $comment
     * @param boolean $getUrlWithAccess
     *
     * @return string|core_ET
     */
    protected static function getVerbalLinkForType($type, $valId, &$comment = NULL, $getUrlWithAccess = FALSE)
    {
        if ($type == 'doc') {
            // Документа
            $doc = doc_Containers::getDocument($valId);
            
            $hnd = '#' . $doc->getHandle();
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            $url = $doc->getSingleUrlArray();
            if (empty($url) && ($getUrlWithAccess)) {
                $url = $doc->getUrlWithAccess($doc->instance, $doc->that);
            }
            
            // Атрибутеите на линка
            $attr = array();
            $attr['ef_icon'] = $doc->getIcon($doc->that);
            $attr['title'] = 'Документ|*: ' . $docRow->title;
            
            $link = ht::createLink($hnd, $url, NULL, $attr);
            
            $folderId = doc_Containers::fetchField($valId, 'folderId');
            if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {
                $fRec = doc_Folders::fetch($folderId);
                $link .= ' « ' . doc_Folders::recToVerbal($fRec, 'title')->title;
            }
            
            if (!trim($comment)) {
                $comment = $docRow->title;
            }
        } elseif ($type == 'file') {
            $clsInst = cls::get('fileman_Files');
            $valId = fileman::idToFh($valId);
            
            expect($valId);
            
            $link = $clsInst->getLinkToSingle($valId);
            
            if (!trim($comment)) {
                $comment = tr("Файл");
            }
        } else {
            expect(FALSE, $type);
        }
        
        return $link;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако се радактира записа, само коментара да може да се сменя
        if ($data->form->rec->id) {
            $data->form->setField('outType', 'input=none');
            $data->form->setField('outVal', 'input=none');
            $data->form->setField('inType', 'input=none');
            $data->form->setField('inVal', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->outType == 'doc') {
            $doc = doc_Containers::getDocument($rec->outVal);
            $doc->touchRec();
        }
        
        if ($rec->inType == 'doc') {
            $doc = doc_Containers::getDocument($rec->inVal);
            $doc->touchRec();
        }
    }
    
    
    /**
     * След инсталация на класа
     *
     * @param doc_linked $mvc
     * @param NULL|string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $res .= fileman_Buckets::createBucket('Linked', 'Файлове във свързаните документи', NULL, '300 MB', 'user', 'user');
    }
}
