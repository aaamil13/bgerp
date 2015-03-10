<?php

/**
 * Име на под-директория  в sbg/EF_APP_NAME, където се намират умалените изображения
 */
defIfNot('THUMB_IMG_DIR', '_tb_');


/**
 * Пълен път до директорията, където се съхраняват умалените картинки
 */
defIfNot('THUMB_IMG_PATH',  EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . THUMB_IMG_DIR);



/**
 * Клас 'thumb_Setup'
 *
 *
 * @category  bgerp
 * @package   minify
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class thumb_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = "Скалиране на картинки";
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = TRUE;
    
        
    protected $folders = THUMB_IMG_PATH; 

    
    /**
     * Пакет без инсталация
     */
    public $noInstall = TRUE;
    
}