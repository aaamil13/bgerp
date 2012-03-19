<?php



/**
 * Клас 'doc_Containers' - Контейнери за документи
 *
 *
 * @category  all
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Containers extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State';
    
    
    /**
     * Заглавие
     */
    var $title = "Документи в нишките";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "created=Създаване,document=Документи,createdOn=";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_ThreadDocuments';
    
    /**
     * @todo Чака за документация...
     */
    var $listItemsPerPage = 100;
    
    /**
     * @todo Чака за документация...
     */
    var $canList = 'user';
    
    /**
     * @todo Чака за документация...
     */
    var $canAdd  = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('handle' , 'varchar', 'caption=Документ->Манипулатор');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
    }
    
    
    /**
     * Филтрира по id на нишка (threadId)
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $data->query->where("#threadId = {$threadId}");
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        expect($data->threadId = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));
        
        $data->folderId = $data->threadRec->folderId;
        
        doc_Threads::requireRightFor('single', $data->threadRec);
        
        bgerp_Recently::add('document', $data->threadRec->firstContainerId);
        
        $data->query->orderBy('#createdOn');
    }
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $title = new ET("<div style='font-size:18px'>[#user#] » [#folder#] ([#folderCover#]) » [#threadTitle#]</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        $title->replace($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        
        // Потребител
        if($folderRec->inCharge) {
            $user = core_Users::fetchField($folderRec->inCharge, 'nick');
        } else {
            $user = '@system';
        }
        $title->replace($user, 'user');
        
        // Заглавие на треда
        $document = $mvc->getDocument($data->threadRec->firstContainerId);
        $docRow = $document->getDocumentRow();
        $docTitle = str::limitLen($docRow->title, 70);
        $title->replace($docTitle, 'threadTitle');
        
        $data->title = $title;
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    function on_AfterRenderListTable($mvc, $tpl, $data)
    {
        $state = $data->threadRec->state;
        $tpl = new ET("<div class='thread-{$state}'>[#1#]</div>", $tpl);
        
        $tpl->appendOnce("var h = window.location.hash.substr(1); var doc=get$(h); doc.style.color = '#006600'; setTimeout( function() {doc.style.color = 'black';}, 1200);", 'ON_LOAD');
    }
    
    
    /**
     * Подготвя някои вербални стойности за полетата на контейнера за документ
     * Използва методи на интерфейса doc_DocumentIntf, за да вземе тези стойности
     * директно от документа, който е в дадения контейнер
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $document = $mvc->getDocument($rec->id);
        $docRow = $document->getDocumentRow();
        
        $data = $document->prepareDocument();
        
        $row->created = new ET("<center><div style='font-size:0.8em;margin-top:5px;'>[#3#]</div>
                                        <div style='font-size:0.8em;margin:5px;margin-bottom:10px;'>[#1#]</div>
                                        <div style='margin:10px;'>[#2#]</div></center>",
            ($row->createdOn),
            avatar_Plugin::getImg($docRow->authorId, $docRow->authorEmail),
            str::limitLen($docRow->author, 32));
        
        // визуализиране на обобщена информация от лога
        $row->created->append(doc_Log::getSummary($rec->id, $rec->threadId));
        
        $row->ROW_ATTR['id'] = $document->getHandle();
        
        // Рендираме изгледа
        $row->document = $document->renderDocument($data);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public function on_AfterPrepareListToolbar($mvc, $data)
    {
        if($data->threadRec->state != 'rejected') {
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'threadId'=>$data->threadId), 'id=btnAdd,class=btn-add');
            
            if($data->threadRec->state == 'opened') {
                $data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId'=>$data->threadId), 'class=btn-close');
            } elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
                $data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId'=>$data->threadId), 'class=btn-open');
            }
            $data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId'=>$data->threadId, 'ret_url' => TRUE), 'class=btn-move');
        }
    }
    
    
    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    function create($class, $threadId, $folderId)
    {
        $className = cls::getClassName($class);
        $rec->docClass = core_Classes::fetchIdByName($className);
        $rec->threadId = $threadId;
        $rec->folderId = $folderId;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - създава я
     *
     * @param int $id key(mvc=doc_Containers)
     */
    function update_($id)
    {
        expect($rec = doc_Containers::fetch($id), $id);
        
        $docMvc = cls::get($rec->docClass);
        
        //$rec->shared = $docMvc->getSharedUsers($rec);
        
        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }
        
        $fields = 'state,folderId,threadId,containerId';
        
        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }
        
        if($mustSave) {
            $bSaved = doc_Containers::save($rec);
        }
    }
    
    
    /**
     * Предизвиква обновяване на треда, след всяко обновяване на контейнера
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($rec->threadId && $rec->docId) {
            doc_Threads::updateThread($rec->threadId);
        }
    }
    
    
    /**
     * Връща обект-пълномощник приведен към зададен интерфейс
     *
     * @param mixed int key(mvc=doc_Containers) или обект с docId и docClass
     * @param string $intf
     * @return object
     */
    static function getDocument($id, $intf = NULL)
    {
        if (!is_object($id)) {
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            // Ако няма id на документ, изчакваме една-две секунди, 
            // защото може този документ да се създава точно в този момент
            if(!$rec->docId) sleep(1);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            if(!$rec->docId) sleep(1);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
        } else {
            $rec = $id;
        }
        
        expect($rec, $id);
        
        return new core_ObjectReference($rec->docClass, $rec->docId, $intf);
    }
    
    
    /**
     * Намира контейнер на документ по негов манипулатор.
     *
     * @param string $handle манипулатор на документ
     * @return int key(mvc=doc_Containers) NULL ако няма съответен на манипулатора контейнер
     */
    public static function getByHandle($handle)
    {
        $id = static::fetchField(array("#handle = '[#1#]'", $handle), 'id');
        
        if (!$id) {
            $id = NULL;
        }
        
        return $id;
    }
    
    
    /**
     * Генерира и връща манипулатор на документ.
     *
     * @param int $id key(mvc=doc_Container)
     * @return string манипулатора на документа
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id, 'id, handle, docId, docClass');
        
        expect($rec);
        
        if (!$rec->handle) {
            $doc = static::getDocument($rec, 'doc_DocumentIntf');
            $rec->handle = $doc->getHandle();
            
            do {
                $rec->handle = email_util_ThreadHandle::protect($rec->handle);
            } while (!is_null(static::getByHandle($rec->handle)));
            
            expect($rec->handle);
            
            // Записваме току-що генерирания манипулатор в контейнера. Всеки следващ 
            // опит за вземане на манипулатор ще връща тази записана стойност.
            static::save($rec);
        }
        
        return $rec->handle;
    }
    
    
    /**
     * Потребителите, с които е споделен документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    public static function getShared($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        return $doc->getShared();
    }
    
    
    /**
     * Състоянието на документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string състоянието на документа
     */
    public static function getDocState($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        $row = $doc->getDocumentRow();
        
        return $row->state;
    }
    
    
    /**
     * Екшън за активиране на постинги
     */
    function act_Activate()
    {
        $containerId = Request::get('containerId');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $class::haveRightFor('activation', $rec);
        
        //Променяме състоянието
        $recAct->id = $rec->id;
        $recAct->state = 'active';
        
        //Записваме данните в БД
        $class::save($recAct);
        
        //Редиректваме към сингъла на съответния клас, от къде се прехвърляме към треда
        redirect(array($class, 'single', $rec->id));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Send()
    {
        $containerId = Request::get('containerId');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $class::haveRightFor('send', $rec);
        
        //Ако нямаме въведен имейл, тогава се редиректва в страницата за изпращане, където можем да въведем съответното поле
        if (!$rec->email) {
            
            $link = array('email_Sent', 'send', 'containerId' => $rec->id);
            
            return new Redirect($link);
        }
        
        //id' то на пощенската кутия на потребителя, който е логнат
        $boxFrom = email_Inboxes::getUserEmailId();
        
        $tpl = '<div style="padding: 1em;">';
        
        //Опциите при изпращане
        $options = NULL;
        
        $Send = cls::get('email_Sent');
        
        //Изпращане на имейл-а
        if ($id = $Send->send($rec->containerId, $rec->email, $rec->subject, $boxFrom, $options)) {
            $tpl = "Успешно изпращане до {$rec->email}";
        } else {
            $tpl = "Проблем при изпращане до {$rec->email}";
        }
        
        $tpl .= ''
        . '<div style="margin-top: 1em;">'
        .    '<input type="button" value="Затваряне" onclick="window.close();" />'
        . '</div>';
        
        $tpl .= '</div>';
        
        return $tpl;
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи,
     * достъпни за дадената нишка. Очаква threadId
     */
    function act_ShowDocMenu()
    {
        expect($threadId = Request::get('threadId', 'int'));
        
        doc_Threads::requireRightFor('single', $threadId);
        
        $tpl = new ET();
        
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        foreach($docArr as $id => $class) {
            
            $mvc = cls::get($class);
            
            if($mvc->canAddToThread($threadId, '') && $mvc->haveRightFor('add')) {
                
                if(!Mode::is('screenMode', 'narrow')) {
                    $attr = "style=background-image:url(" . sbf($mvc->singleIcon, '') . ");";
                } else {
                    $attr = array();
                }
                $tpl->append(ht::createBtn($mvc->singleTitle, array($class, 'add', 'threadId'=>$threadId), NULL, NULL, $attr));
                
                $tpl->append('<br>');
            }
        }
        
        return $this->renderWrapping($tpl);
    }
}