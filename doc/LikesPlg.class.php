<?php


/**
 * Плъгин за харесвания на документите
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_LikesPlg extends core_Plugin
{
    
    
 	/**
     * Брой харесали потребители, над които няма да се показват имената им
     */
    static $notifyNickShowCnt = 3;
    
    
 	/**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Дали мжое да се редактират активирани документи
        setIfNot($mvc->canLike, 'user');
        setIfNot($mvc->canDislike, 'user');
    }
    
    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if ($mvc->haveRightFor('like', $data->rec->id)) {
            $data->toolbar->addBtn("Харесвам", array($mvc, 'likeDocument', $data->rec->id),
            "id=btnLike{$data->rec->containerId}, row=2, order=19.4,title=" . tr('Харесване на документа'),  'ef_icon = img/16/redheart.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec) {
            if ($action == 'like') {
                if (($rec->state == 'draft') || 
                    ($rec->state == 'rejected') || 
                    !$mvc->haveRightFor('single', $rec->id) || 
                    doc_Likes::isLiked($rec->containerId, $userId)) {
                    
                        $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'dislike') {
                if (($rec->state == 'draft') || 
                    ($rec->state == 'rejected') ||
                    !doc_Likes::isLiked($rec->containerId, $userId) ||
                    !$mvc->haveRightFor('single', $rec->id)) {
                    
                        $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param core_Redirect $res
     * @param string $action
     * 
     * @return NULL|boolean
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        $action = strtolower($action);
        
        if (($action != 'likedocument') && ($action != 'dislikedocument') && ($action != 'showlikes')) return ;
        
        $id = Request::get('id', 'int');
        
        $rec = $mvc->fetch($id);
        
        expect($rec);
        
        if ($action == 'likedocument') {
            
            // Харесване
            
            $redirect = TRUE;
            
            $mvc->requireRightFor('like', $rec);
            
            if (doc_Likes::like($rec->containerId)) {
                $mvc->logInfo('Харесване', $rec->id);
                $mvc->touchRec($rec->id);
                
                $mvc->notifyUsersForLike($rec);
            }
        } elseif ($action == 'dislikedocument') {
            
            // Премахване на харесаване
            
            $redirect = TRUE;
            
            $mvc->requireRightFor('dislike', $rec);
            
            if (doc_Likes::dislike($rec->containerId)) {
                $mvc->logInfo('Премахнато харесване', $rec->id);
                $mvc->touchRec($rec->id);
            }
        } elseif ($action == 'showlikes') {
            
            // Показване на екшъните по ajax
            
            expect(Request::get('ajax_mode'));
            
            $redirect = FALSE;
            
            $html = self::getLikesHtml($rec->containerId);
            
            $resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => self::getElemId($rec), 'html' => $html, 'replace' => TRUE);
    		
            $res = array($resObj);
            
        }
        
        if ($redirect) {
            $res = new Redirect(array($mvc, 'single', $id));
        }
        
        return FALSE;
    }
    
    
    /**
     * Нотифицира потребителите, които са харесали документа за нови харесвания
     * 
     * @param core_Master $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterNotifyUsersForLike($mvc, &$res, $rec)
    {
        $likedArr = doc_Likes::getLikedArr($rec->containerId, 'DESC');
        $likedArrCnt = count($likedArr);
        
        // Ако само текущия потребител е харесал документа
        if (!$likedArr || ($likedArrCnt == 1)) return ;
        
        $currUserId = core_Users::getCurrent();
        
        $documentTitle = $mvc->getTitleForId($rec->id, FALSE);
        
        foreach ($likedArr as $key => $lRec) {
            if ($lRec->createdBy == $currUserId) continue;
            
            if (!$mvc->haveRightFor('single', $rec->id, $lRec->createdBy)) continue;
            
            $cLikedArr = $likedArr;
            unset($cLikedArr[$key]);
            
            $notifyStr = self::prepareNotifyStr($cLikedArr) . ' "' . $documentTitle . '"';
            
            if ($notifyStr) {
                $document = doc_Containers::getDocument($lRec->containerId);
                $clearUrl = $linkUrl = array($mvc, 'single', $rec->id);
                $clearUrl['like'] = TRUE;
                bgerp_Notifications::add($notifyStr, $clearUrl, $lRec->createdBy, 'normal', $linkUrl);
            }
        }
    }
    
    
    /**
     * Връща стринг за нотификация с потребителите, които са харесали
     * 
     * @param array $recArr
     * 
     * @return string
     */
    protected static function prepareNotifyStr($recArr)
    {
        $i = 0;
        $otherCnt = 0;
        $notifyStr .= '';
        foreach ((array)$recArr as $rec) {
            $nick = core_Users::getNick($rec->createdBy);
            $nick = type_Nick::normalize($nick);
            
            $i++;
            
            if (self::$notifyNickShowCnt >= $i) {
                $notifyStr .= $notifyStr ? ', ' : '';
                $notifyStr .= $nick;
            } else {
                $otherCnt++;
            }
        }
        
        if ($otherCnt) {
            $notifyStr .= ' |и още|* ' . $otherCnt . ' |също харесват|*';
        } else {
            if ($i == 1) {
                $notifyStr .= ' |също харесва|*';
            } elseif ($i > 1) {
                $notifyStr .= ' |също харесват|*';
            }
        }
        
        return $notifyStr;
    }
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        // Ако не сме в xhtml режим
        if (!Mode::is('text', 'xhtml')) {
            
            // Изчистваме нотификацията за харесване
            $url = array($mvc, 'single', $data->rec->id, 'like' => TRUE);
            bgerp_Notifications::clear($url);
        }
    }
    
    
    /**
     * Рендира лога за харесванията
     * 
     * @param integer $cid
     * 
     * @return string
     */
    protected static function getLikesHtml($cid)
    {
        $html = '';
        
        $likedArr = doc_Likes::getLikedArr($cid, 'DESC');
            
        if ($likedArr) {
            
            foreach ($likedArr as $likeRec) {
                $nick = crm_Profiles::createLink($likeRec->createdBy);
                $likeDate = mb_strtolower(core_DateTime::mysql2verbal($likeRec->createdOn, 'smartTime'));
                $likeDate = " ({$likeDate})";
                
                $html .= "<div class='nowrap'>" . $nick . $likeDate . "</div>";
            }
        }
        
        return $html;
    }
    
    
    /**
     * 
     * 
     * @param core_Master $invoker
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        if ($fields && $fields['-single']) {
            
            if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
                
                if ($rec->state != 'draft' && $rec->state != 'rejected') {
                    
                    // Добавяме харесванията и линк
                    $isLikedFromCurrUser = doc_Likes::isLiked($rec->containerId, core_Users::getCurrent());
                    
                    if ($isLikedFromCurrUser) {
                        $likeArrUrl = array();
                        if ($mvc->haveRightFor('dislike', $data->rec->id)) {
                            $likeArrUrl = array($mvc, 'dislikeDocument', $rec->id);
                        }
                        
                        $likesLink = ht::createLink('', $likeArrUrl, NULL, 'ef_icon=img/16/redheart.png,class=liked, title=' . tr('Отказ от харесване'));
                    } else {
                        $dislikeArrUrl = array();
                        if ($mvc->haveRightFor('like', $data->rec->id)) {
                            $dislikeArrUrl = array($mvc, 'likeDocument', $rec->id);
                        }
                        
                        $likesLink = ht::createLink('', $dislikeArrUrl, NULL, 'ef_icon=img/16/grayheart.png,class=disliked, title=' . tr('Харесване'));
                    }
                    
                    $likesCnt = doc_Likes::getLikesCnt($rec->containerId);
                    
                    if ($likesCnt) {
                        $attr['class'] = 'showLikes tooltip-arrow-link';
                        $attr['title'] = tr('Показване на харесванията');
                        $attr['data-url'] = toUrl(array($mvc, 'showLikes', $rec->id), 'local');
                        $attr['data-useHover'] = '1';
                        $attr['onClick'] = 'startUrlFromDataAttr(this)';
                        
                        $likesCntLink = ht::createElement('span', $attr, $likesCnt);
                        
                        $likesCntLink .= '<span class="likeCnt"></span>';
                        
                        $likesLink = $likesLink . $likesCntLink;
                        
                        $elemId = self::getElemId($rec);
                        
                        $likesLink = "{$likesLink}<div class='additionalInfo-holder'><span class='additionalInfo' id='{$elemId}'></span></div>";
                    }
                    
                    $row->DocumentSettings = new ET($row->DocumentSettings);
                    $row->DocumentSettings->append($likesLink);
                
                }
            }
        }
    }
    
    
    /**
     * Връща id за html елемент
     * 
     * @param stdObject $rec
     * 
     * @return string
     */
    protected static function getElemId($rec)
    {
        
        return 'showLikes_' . $rec->containerId;
    }
}
