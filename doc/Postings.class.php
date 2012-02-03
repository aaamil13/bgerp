<?php 


/**
 * Текста, който ще се показва в хедърната част на постингите
 */
defIfNot('BGERP_POSTINGS_HEADER_TEXT', 'Препратка');


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Postings extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Постинги";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Коментар";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за имейли-те?
     */
    var $canEmail = 'admin, email';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, email_plg_Document, doc_ActivatePlg';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutPostings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/doc_text_image.png';
    
    
    /**
     * Кой таб да е активен, при натискане на таба на този класа
     */
    var $currentTab = 'doc_Postings';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Postings)', 'caption=Съобщение,mandatory');
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
        $this->FLD('email', 'email', 'caption=Адресант->Имейл');
        $this->FLD('phone', 'varchar', 'caption=Адресант->Тел.');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
        $this->FLD('sharedUsers', 'keylist(mvc=core_Users,select=nick)', 'caption=Споделяне->Потребители');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        
        //Ако редактираме данните, прескачаме тази стъпка
        if (!$rec->id) {
            //Ако имаме originId и добавяме нов запис
            if ($rec->originId) {
                
                //Добавяме в полето Относно отговор на съобщението
                $oDoc = doc_Containers::getDocument($rec->originId);
                $oRow = $oDoc->getDocumentRow();
                $rec->subject = 'RE: ' . $oRow->title;
                
                //Взема документа, от който е постинга
                $document = doc_Containers::getDocument($rec->originId);
                
                //Ако класа на документа, на който ще пишем коментара
                //не е doc_Postings тогава взема данните от най стария постинг, с най - много добавени линии
                //и скриваме всички полета за адресант
                if ($document->className != 'doc_Postings') {
                    $contragentData = doc_Threads::getContragentData($rec->threadId);
                } else {
                    $form = $data->form;
                    $form->setField("recipient", 'input=none');
                    $form->setField("attn", 'input=none');
                    $form->setField("email", 'input=none');
                    $form->setField("phone", 'input=none');
                    $form->setField("fax", 'input=none');
                    $form->setField("country", 'input=none');
                    $form->setField("pcode", 'input=none');
                    $form->setField("place", 'input=none');
                    $form->setField("address", 'input=none');
                }
                
                //Ако класа няма интерфейс doc_ContragentDataIntf, тогава му добавя хедър,
                //с линк към текущия документ. Добавя и футър.
                if (!Cls::haveInterface('doc_ContragentDataIntf', $document->instance)) {
                    $header = $this->getHeader($document->getHandle());
                    $footer = $this->getFooter();
                    $rec->body = $header . "\n\n\n" . $footer;
                    
                }
                
            } else {
                //Ако нямаме originId, а имаме emailto
                if ($emailTo = Request::get('emailto')) {
                    //Вземаме данните от контакти->фирма
                    $contragentData = crm_Companies::getRecipientData($emailTo);
                    
                    //Ако няма контакти за фирма, вземаме данние от контакти->Лица
                    if (!$contragentData) {
                        $contragentData = crm_Persons::getRecipientData($emailTo);
                    }
                    
                    $contragentData = doc_Threads::clearArray($contragentData);
                    
                    $contragentData['email'] = $emailTo;
                    
                    //Форсираме да създадем папка. Ако не можем, тогава запазваме старота папка (Постинг)
                    if ($folderId = email_Router::getEmailFolder($contragentData['email'])) {
                        $rec->folderId = $folderId;
                    }
                }
            }
            
            //Ако сме открили някакви данни за получателя
            if (count($contragentData)) {
                $contragentData = (object)$contragentData;
                
                //Заместваме данните в полетата с техните стойности
                $rec->recipient = $contragentData->recipient;
                $rec->attn = $contragentData->attn;
                $rec->phone = $contragentData->phone;
                $rec->fax = $contragentData->fax;
                $rec->country = $contragentData->country;
                $rec->pcode = $contragentData->pcode;
                $rec->place = $contragentData->place;
                $rec->address = $contragentData->address;
                $rec->email = $contragentData->email;
            }
        }
    }
    
    
    /**
     * Преди вкарване на записите в модела
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        //Преди да запишем данните, проверяваме дали имаме шаблон за подпис и го заместваме
        if ((stripos($rec->body, '[#sign#]') !== FALSE) || (stripos($rec->body, '[#podpis#]') !== FALSE)) {
            $footer = $this->getFooter();
            $rec->body = str_ireplace('[#sign#]', $footer, $rec->body);
            $rec->body = str_ireplace('[#podpis#]', $footer, $rec->body);
        }
    }
    
    
    /**
     * Създава хедър към постинга
     */
    function getHeader($handle)
    {
        //Хедъра на постинга
        $header = BGERP_POSTINGS_HEADER_TEXT . ' #'. $handle;
        
        return $header;
    }
    
        
    /**
     * Създава футър към постинга
     */
    function getFooter()
    {
        //Зареждаме текущия език
        $lang = core_Lg::getCurrent();
        
        //Зареждаме класа, за да имаме достъп до променливите
        cls::load('crm_Companies');
        
        $companyId = BGERP_OWN_COMPANY_ID;
        
        //Вземаме данните за нашата фирма
        $myCompany = crm_Companies::fetch($companyId);
        
        $userName = core_Users::getCurrent('names');
        
        //Ако езика е на български да не се показва държавата
        if (strtolower($lang) != 'bg') {
            $country = crm_Companies::getVerbal($myCompany, 'country');
        }
        
        $tpl = new ET(tr(getFileContent("doc/tpl/GreetingPostings.shtml")));
        
        //Заместваме шаблоните
        $tpl->replace($userName, 'name');
        $tpl->replace($country, 'country');
        $tpl->replace($myCompany->pCode, 'pCode');
        $tpl->replace($myCompany->place, 'city');
        $tpl->replace($myCompany->address, 'street');
        $tpl->replace($myCompany->name, 'company');
        $tpl->replace($myCompany->tel, 'tel');
        $tpl->replace($myCompany->fax, 'fax');
        $tpl->replace($myCompany->email, 'email');
        $tpl->replace($myCompany->website, 'website');
        
        //Изчисва всички празни редове
        $footer = $this->clearEmptyLines($tpl->getContent());
        
        return $footer;
    }
    
    
    /**
     * Изчиства празните редове
     */
    function clearEmptyLines($content)
    {
        //Всеки ред го слагаме в отделен масив
        $arrContent = explode("\n", $content);
        
        //Премахваме редовете, които нямат текст, а само интервали
        if (is_array($arrContent)) {
            foreach ($arrContent as $value) {
                if (!str::trim($value)) continue;
                
                $clearContent .= $value . "\r\n";
            }
            $content = $clearContent;
        }
                
        return $clearContent;
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, $data)
    {
        if (Mode::is('text', 'plain')) {
            // Форматиране на данните в $data->row за показване в plain text режим
            
            $width = 80;
            $leftLabelWidth = 19;
            $rightLabelWidth = 11;
            $columnWidth = $width / 2;
            
            $row = $data->row;
            
            // Лява колона на антетката
            foreach (array('modifiedOn', 'subject', 'recipient', 'attentionOf', 'refNo') as $f) {
                $row->{$f} = strip_tags($row->{$f});
                $row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $leftLabelWidth, $leftLabelWidth);
            }
            
            // Дясна колона на антетката
            foreach (array('email', 'phone', 'fax', 'address') as $f) {
                $row->{$f} = strip_tags($row->{$f});
                $row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $rightLabelWidth, $columnWidth + $rightLabelWidth);
            }
            
            $row->body = type_Text::formatTextBlock($row->body, $width, 0);
            $row->hr = str_repeat('-', $width);
        }
        
        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
        
        if($data->rec->recipient || $data->rec->attn || $data->rec->email) {
            $data->row->headerType = tr('Писмо');
        } elseif($data->rec->originId) {
            $data->row->headerType = tr('Отговор');
        } else {
            $threadRec = doc_Threads::fetch($data->rec->threadId);
            
            if($threadRec->firstContainerId == $data->rec->containerId) {
                $data->row->headerType = tr('Съобщение');
            } else {
                $data->row->headerType = tr('Съобщение');
            }
        }
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function on_AfterRenderSingleLayout($mvc, $tpl, &$data)
    {
        //Полета за адресанта   
        $allData = $data->row->recipient . $data->row->attn . $data->row->email . $data->row->phone .
        $data->row->fax . $data->row->country . $data->row->pcode . $data->row->place . $data->row->address;
        $allData = str::trim($allData);
        
        //Ако нямаме въведени данни за адресанта, тогава не показваме антетката
        if (!$allData) {
            //Темата е на мястото на singleTitle
            $data->row->singleTitle = $data->row->subject;
            
            $data->row->subject = NULL;
            $data->row->createdDate = NULL;
            $data->row->handle = NULL;
        }
        
        if (Mode::is('text', 'plain')) {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostings.txt')));
        } else {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostings.shtml')));
        }
        
        $tpl->replace(static::getBodyTpl(), 'DOC_BODY');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     * Шаблон за тялото на съобщение в документната система.
     *
     * Използва се в този клас, както и в blast_Emails
     *
     * @return ET
     */
    static function getBodyTpl()
    {
        if (Mode::is('text', 'plain')) {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostingsBody.txt')));
        } else {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostingsBody.shtml')));
        }
        
        return $tpl;
    }
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Прикачените към документ файлове
     *
     * @param int $id ид на документ
     * @return array
     */
    public function getEmailAttachments($id)
    {
        
        /**
         * @TODO
         */
        return array();
    }
    
    
    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     *
     * @TODO това ще е полето subject на doc_Posting, когато то бъде добавено.
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return static::fetchField($id, 'subject');
    }
    
    
    /**
     * До кой е-мейл или списък с е-мейли трябва да се изпрати писмото
     *
     * @param int $id ид на документ
     */
    public function getDefaultEmailTo($id)
    {
        return static::fetchField($id, 'email');
    }
    
    
    /**
     * Адреса на изпращач по подразбиране за документите от този тип.
     *
     * @param int $id ид на документ
     * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
     */
    public function getDefaultBoxFrom($id)
    {
        // Няма смислена стойност по подразбиране
        return NULL;
    }
    
    
    /**
     * Писмото (ако има такова), в отговор на което е направен този постинг
     *
     * @param int $id ид на документ
     * @return int key(email_Messages) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        
        /**
         * @TODO
         */
        return NULL;
    }
    
    
    /**
     * ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf
     */
    public function getHandle($id)
    {
        return 'T' . $id;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row->title = $subject;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    function getContragentData($id)
    {
        $posting = doc_Postings::fetch($id);
        
        $contrData->recipient = $posting->recipient;
        $contrData->attn = $posting->attn;
        $contrData->phone = $posting->phone;
        $contrData->fax = $posting->fax;
        $contrData->country = $posting->country;
        $contrData->pcode = $posting->pcode;
        $contrData->place = $posting->place;
        $contrData->address = $posting->address;
        $contrData->email = $posting->email;
        
        return $contrData;
    }
}