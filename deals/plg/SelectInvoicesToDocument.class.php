<?php

/**
 * Плъгин позволяващ на документ да се посочва към кои фактури е
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_SelectInvoicesToDocument extends core_Plugin
{

    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    protected static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->FLD('fromContainerId', 'int', 'caption=Към,input=hidden,silent');
    }


    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            if(isset($form->rec->id)){
                $form->rec->_isEdited = true;
            }
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        if(isset($rec->id) && $rec->_isEdited){
            $oData = $mvc->getPaymentData($rec->id);
            $nData = $mvc->getPaymentData($rec);

            if($oData->amount != $nData->amount || $oData->currencyId != $nData->currencyId){
                $rec->_resetInvoices = true;
            }
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if($rec->_resetInvoices){
            deals_InvoicesToDocuments::delete("#documentContainerId = {$rec->containerId}");
        }

        if(isset($rec->fromContainerId)){
            $amount = $mvc->getPaymentData($rec)->amount;
            $dRec = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $rec->fromContainerId, 'amount' => $amount);
            deals_InvoicesToDocuments::save($dRec);
        }
    }


    /**
     * Добавя бутон за настройки в единичен изглед
     *
     * @param stdClass $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако има права за отпечатване
        if ($mvc->haveRightFor('selectinvoice', $data->rec)) {
            $data->toolbar->addBtn('Към фактури', array('deals_InvoicesToDocuments', 'selectinvoice', 'documentId' => $data->rec->id, 'documentClassId' => $mvc->getClassId(), 'ret_url' => true), 'ef_icon=img/16/edit.png, order=30, title=Избор на фактури към които е документа');
        }
    }


    /**
     * Изпълнява се след закачане на детайлите
     */
    protected static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['Invoices'] = 'deals_InvoicesToDocuments';
        $details = arr::fromArray($details);
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }

        if(isset($rec->containerId)){
            $invoicesArr = deals_InvoicesToDocuments::getInvoiceArr($rec->containerId);
            foreach ($invoicesArr as $iRec) {
                $invRec = sales_Invoices::fetch("#containerId = {$iRec->containerId}", 'number');
                $numberPadded = sales_Invoices::getVerbal($invRec, 'number');

                $res .= ' ' . plg_Search::normalizeText($invRec->number) . ' ' . plg_Search::normalizeText($numberPadded);
            }
        }
    }


    /**
     * Опциите за избор на основание
     */
    public static function on_AfterGetReasonContainerOptions($mvc, &$res, $rec)
    {
        $threadsArr = array($rec->threadId => $rec->threadId);

        // Ако в документа е разрешено да се показват ф-те към обединените сделки
        if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
            $closedDocuments = keylist::toArray($firstDocument->fetchField('closedDocuments'));
            if(countR($closedDocuments)){
                $docQuery = $firstDocument->getQuery();
                $docQuery->in('id', $closedDocuments);
                $docQuery->show('threadId');
                $threadsArr += arr::extractValuesFromArray($docQuery->fetchAll(), 'threadId');
            }
        }

        $res = ($rec->isReverse == 'yes') ? deals_Helper::getInvoicesInThread($threadsArr, null, false, false, true) : deals_Helper::getInvoicesInThread($threadsArr, null, true, true, false);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectinvoice' && isset($rec)) {
            $hasInvoices = $mvc->getReasonContainerOptions($rec);

            if ($rec->state == 'rejected' || !$hasInvoices) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
