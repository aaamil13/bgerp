<?php

/**
 * Рутира всички несортирани писма.
 * 
 * Несортирани са всички писма от папка "Несортирани - [Титлата на класа email_Messages]"
 *
 * @category   BGERP
 * @package    email
 * @author	   Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see https://github.com/bgerp/bgerp/issues/108
 */
class email_Router extends core_Manager
{   
    var $loadList = 'plg_Created,email_Wrapper';

    var $title    = "Рутер на ел. поща";

    var $listFields = 'id, type, key, folderId';

    var $canRead   = 'admin,email';
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    
    /**
     *  Име на папката, където отиват писмата неподлежащи на сортиране
     */ 
    const UnsortableFolderName = 'Unsorted - Internet';

    /**
     *  Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на 
     *  по-адекватно сортиране
     */ 
    const UnsortableCountryFolderName = 'Unsorted - %s';


    function description()
    {
        $this->FLD('type' , 'enum(fromTo, from, sent, domain)', 'caption=Тип');
        $this->FLD('key' , 'varchar(64)', 'caption=Ключ');
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папка');
        
        defIfNot('UNSORTABLE_EMAILS', self::UnsortableFolderName);
        defIfNot('UNSORTABLE_COUNTRY_EMAILS', self::UnsortableCountryFolderName);
    }
    
    /**
     * Рутира всички нерутирани до момента писма.
     * 
     * Нерутирани са писмата, намиращи се в специална папка за нерутирани писма
     *
     */
    function routeAll($limit = 10)
    {
    	$incomingQuery    = email_Messages::getQuery();
    	$incomingFolderId = email_Messages::getUnsortedFolder();

    	$incomingQuery->where("#folderId = {$incomingFolderId}");
    	$incomingQuery->limit($limit);
    	
    	while ($emailRec = $incomingQuery->fetch()) {
    		if ($location = $this->route($emailRec)) {
    			email_Messages::move($emailRec, $location);
    		}
    	}
    }
    
    function act_RouteAll() {
    	$this->routeAll();
    }
    
    /**
     * Рутира писмо.
     * 
     * Формално, задачата на този метод е да определи максимално смислени стойности на полетата
     * $rec->folderId и $rec->threadId.
     * 
     * Определянето на тези полета зависи от предварително дефинирани правила
     * (@see https://github.com/bgerp/bgerp/issues/108):
     * 
     * - Според треда или InReplayTo хедъра. Информация за треда - от email_Sent
     * - Според пощенската кутия на получателя, ако тя не е generic
     * - Според FromTo правилата
     * - Според From правилата
     * - Според Sent правилата
     * - Според наличните данни във визитките (Това е за отделен клас)
     * - Според domain правилата
     * - Според държавата на изпращача (unsorted държава()
     * - Останалите несортирани в Unsorted - Internet.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    function route($rec)
    {
    	static $routeRules = array(
    		'Thread',
    		'BypassAccount',
    		'Recipient',
    		'FromTo',
    		'Sender',
    		'Sent',
    		'Crm',
    		'Domain',
    		'Country',
    		'Unsorted',
    	);
    	
    	foreach ($routeRules as $rule) {
    		$method = 'routeBy' . $rule;
    		if (method_exists($this, $method)) {
    			$location = $this->{$method}($rec);
    			if (!is_null($location->folderId) || !is_null($location->threadId)) {
    				return $location;
    			}
    		}
    	}
    	
    	// Задължително поне едно от правилата би трябвало да сработи!
    	expect(FALSE);
    }
    
    /**
     * Правило за рутиране към съществуваща нишка (thread).
     *
     * Извлича при възможност нишката в която да отиде писмото.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByThread($rec)
    {
    	/*
    	 * @TODO: 
    	 * 
    	 * инспектиране на InReplyTo: MIME хедъра; 
    	 * инспектиране на Subject
    	 * 
    	 * ако има валиден тред - това е резултата
    	 * 
    	 * Информация за валидността на тред се съдържа в модела на изпратените писма
    	 * @see email_Sent
    	 * 
    	 */
    }
    
    /**
     * Рутиране на писма, изтеглени от "bypass account"
     * 
     * Bypass account e запис от модела @see email_Accounts, за който е указано, че писмата му
     * не подлеждат на стандартното сортиране и се разпределят директно в папкана на акаунта.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByBypassAccount($rec)
    {
    	$location = new doc_Location();

    	if ($this->isBypassAccount($rec->accId)) {
	    	$location->folderId = $this->forceAccountFolder($rec->accId); 
    	}
	    	
    	return $location;
    }
    
    
    function isBypassAccount($accountId)
    {
    	$isBypass = FALSE;
    	
    	if ($accountId) {
    		$isBypass = email_Accounts::fetchField($accountId, 'bypassRoutingRules');
    	}
    	
    	return $isBypass;
    }
    
    
    function forceAccountFolder($accountId)
    {
    	return email_Accounts::forceCoverAndFolder(
    		(object)array(
    			'id' => $accountId
    		)
    	);
    }
    
    
    /**
     * Правило за рутиране според пощенската кутия на получателя
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByRecipient($rec)
    {
    	/*
    	 * @TODO
    	 * 
    	 * определяне на получателя - $to; 
    	 * намиране на папката на получателя - това е резултата
    	 */
    }
    
    
    /**
     * Правило за рутиране според <From, To> (type = 'fromTo')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByFromTo($rec)
    {
    	return $this->routeByRule('fromTo', $rec);
    }
    
    
    /**
     * Правило за рутиране според изпращача на писмото (type = 'from')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeBySender($rec)
    {
    	return $this->routeByRule('from', $rec);
    }
    
    
    /**
     * Правило за рутиране според изпращача на писмото (type = 'sent')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeBySent($rec)
    {
    	return $this->routeByRule('sent', $rec);
    }
    
    
    /**
     * Правило за рутиране според данните за изпращача, налични в CRM
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByCrm($rec)
    {
    	/*
    	 * @TODO
    	 * 
    	 * намираме визитката на изпращача в CRM и определяме папката според информацията в 
    	 * нея.
    	 */
    }
    
    
    /**
     * Правило за рутиране според домейна на имейл адреса на изпращача (type = 'domain')
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByDomain($rec)
    {
    	return $this->routeByRule('domain', $rec);
    }
    
    
    /**
     * Правило за рутиране според държавата на изпращача.
     * 
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа; NULL ако не може да се рутира.
     */
    private function routeByCountry($rec)
    {
    	// $rec->country съдържа key(mvc=drdata_Countries)

    	$location = new doc_Location();
    	$location->folderId = $this->forceCountryFolder($rec->country); 
    	
    	return $location;
    }
    
    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    function forceCountryFolder($countryId)
    {
    	$folderId = NULL;
    	
    	if ($countryId) {
    		$countryName = drdata_Countries::fetchField($countryId);
    	}
    	
    	if (!empty($countryName)) {
    		$folderId = email_Unsorted::forceCoverAndFolder(
    			(object)array(
    				'name' => sprintf(UNSORTABLE_COUNTRY_EMAILS, $countryName)
    			)
    		);
    	}
    	
    	return $folderId;
    }
    

    /**
     * Прехвърляне на писмо в нарочна папка за несортируеми писма (@see email_Router::UnsortableFolderName)
     * 
     * Последната инстанция в процеса за сортиране на писма. Това правило сработва ако никое 
     * друго не е дало резултат.
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return doc_Location новото местоположение на документа.
     */
    private function routeByUnsorted($rec)
    {
		$location = new doc_Location();
    	$location->folderId = email_Unsorted::forceCoverAndFolder(
    		(object)array(
    			'name' => UNSORTABLE_EMAILS
    		)
    	);
    	
    	return $location;
    }
    
    /**
     * Намира и прилага за писмото записано правило от даден тип.
     *
     * @param string $type (fromTo | from | sent | domain)
     * @param StdClass $rec запис на модела @link email_Messages
     */
    private function routeByRule($type, $rec)
    {
    	// изчисляваме ключа според типа (и самото писмо) 
    	$key = $this->getRuleKey($type, $rec);
    	
    	if ($key === FALSE) {
    		// Неуспех при изчислението на ключ - правилото пропада.
    		return;
    	}

    	// Извличаме (ако има) правило от тип $type и с ключ $key
    	$ruleRec = $this->fetchRule($type, $key);
    	
    	if ($ruleRec->folderId) {
			$location = new doc_Location();
    		$location->folderId = $ruleRec->folderId;
    	}

    	return $location;
    }
    
    /**
     * Извлича от БД правило от определен тип и с определен ключ 
     *
     * @param string $type
     * @param string $key
     */
    function fetchRule($type, $key)
    {
    	$query = $this->getQuery();
    	$ruleRec = $query->fetch("#type = '{$type}' AND #key = '{$key}'");
    }
    
    
    /**
     * Намира ключа от даден тип за писмото $rec
     * 
     * Ключа се определя от типа и данни в самото писмо.
     *
     * @param string $type (fromTo | from | sent | domain)
     * @param StdClass $rec запис на модела @link email_Messages
     */
    function getRuleKey($type, $rec)
    {
    	$key = false;
    	
    	switch ($type) {
    		case 'fromTo':
    			if ($rec->from && $rec->to) {
    				$key = $rec->from . '|' . $rec->to;
    			}
    			break;
    		case 'from':
    			if ($rec->from) {
    				$key = $rec->from;
    			}
    			break;
    		case 'sent':
    			/*
    			 * TODO: НЕ Е ЯСНО!!!
    			 */
    			break;
    		case 'domain':
    			$key = $this->extractDomain($rec->from);
    			break;
    	}
    	
    	return $key;
    }
    
    /**
     * Извлича домейна на имейл адрес
     *
     * @param string $email
     * @return string FALSE при проблем с извличането на домейна
     */
    function extractDomain($email)
    {
    	list(, $domain) = explode('@', $email, 2);
    	
    	if (empty($domain)) {
    		$domain = FALSE;
    	}
    	return $domain;
    }
}