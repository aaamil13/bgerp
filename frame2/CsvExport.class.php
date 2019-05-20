<?php


/**
 * Експортиране на справките като csv
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_CsvExport extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на справка като CSV';
    
    
    /**
     *  Интерфейси
     */
    public $interfaces = 'export_ExportTypeIntf, export_ToXlsExportIntf';
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        $canUse = export_Export::canUseExport($clsId, $objId);
        if (!$canUse) {
            
            return $canUse;
        }
        
        return $clsId == frame2_Reports::getClassId();
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'CSV файл';
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return string|NULL
     */
    public function makeExport($form, $clsId, $objId)
    {
        $Frame = cls::get($clsId);
        $frameRec = $Frame->fetchRec($objId);
        
        doclog_Documents::saveAction(array('action' => doclog_Documents::ACTION_EXPORT, 'containerId' => $frameRec->containerId, 'threadId' => $frameRec->threadId,));
        doclog_Documents::flushActions();
        
        // Ако е избрана версия експортира се тя
        if ($versionId = frame2_Reports::getSelectedVersionId($objId)) {
            if ($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')) {
                $frameRec = $versionRec;
            }
        }
        
        // Подготовка на данните
        $lang = null;
        $csvRecs = $fields = array();
        if ($Driver = $Frame->getDriver($frameRec)) {
            $lang = $Driver->getRenderLang($frameRec);
            if(isset($lang)){
                core_Lg::push($lang);
            }
            
            $csvRecs = $Driver->getExportRecs($frameRec, $this);
            $fields = $Driver->getCsvExportFieldset($frameRec);
        }
        
        // Ако има данни за експорт
        if (count($csvRecs)) {
            
            // Създаване на csv-то
            $csv = csv_Lib::createCsv($csvRecs, $fields);
            $csv .= "\n";
            
            if(isset($lang)){
                core_Lg::pop();
            }
            
            // Подсигуряване че енкодига е UTF8
            $csv = mb_convert_encoding($csv, 'UTF-8', 'UTF-8');
            $csv = iconv('UTF-8', 'UTF-8//IGNORE', $csv);
            
            // Записване във файловата система
            $fileName = $Frame->getHandle($objId) . '-' . str::removeWhitespaces(str::utf2ascii($frameRec->title), '_');
            $fileHnd = fileman::absorbStr($csv, 'exportCsv', "{$fileName}.csv");
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($frameRec->containerId, $fileId, 'doc', 'file');
        }
        
        if (isset($fileHnd)) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/csv.png, title=Сваляне на документа');
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
            $Frame->logWrite('Експорт на CSV', $objId);
        } else {
            $form->info .= "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }
        
        return $fileHnd;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        $link = ht::createLink('CSV', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/csv.png'));
        
        return $link;
    }
}
