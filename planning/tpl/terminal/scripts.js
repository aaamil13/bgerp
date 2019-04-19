
function planningActions() {
	var cookieId = $("#nameHolder").attr("data-id");
	var cookieName = 'terminalTab' + cookieId;
	
	$("input[name=serial]").focus();
	disableScale();

	$('#numPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.quantityField')
	});

	$('#weightPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.weightField')
	});

	$('#serialPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.serialField')
	});

	$(document.body).on('click', ".nmpd-target", function(e){
		$(this).siblings('input').addClass('highlight');
	});

	$(document.body).on('click', ".navigation li.disabled a", function(e){
		stopBtnDefault(e);
	});


	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', ".tab-link", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		if($(this).parent().hasClass( "disabled" )){
			return;
		}
		
		resObj = new Object();
		resObj['url'] = url;

		getEfae().process(resObj);
		
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		if($('.select2').length){
			$('select').trigger("change");
		}
	});
	
	
	// Изпращане на формата за прогреса
	$(document.body).on('click', "#sendBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var serial = $("input[name=serial]").val();
		var action = $("#actionIdSelect").is('[readonly]') ? $("input[name=action]").val() :  $("#actionIdSelect").val() ;
		var res = action.split('|');
		var type = res[0];
		var productId = res[1];
		
		var quantity = $("input[name=quantity]").val();
		var employees = [];
		$('input[id^="employees"]:checked').each(function () {
			employees.push($(this).val());
		});
		var weight = $("input[name=weight]").val();
		var fixedAsset = $("#fixedAssetSelect").val();
		var taskId = $("input[name=taskId]").val();

		var data = {serial:serial,taskId:taskId,productId:productId,quantity:quantity,employees:employees,fixedAsset:fixedAsset,weight:weight,type:type};
		getEfae().process(resObj, data);
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		if($('.select2').length){
			$('select').trigger("change");
		}
		
		
	});

	$(document.body).on('click', ".changeTab ", function(e){
		setCookie('terminalTab' + cookieId, "tab-progress");
	});
	
	var menutabInfo = getCookie(cookieName);
	var currentTab = $('#' + menutabInfo).addClass('active').find('a').attr('href');
	$('.tabContent' + currentTab).addClass('active');

	// Скриване на табовете
	$(document.body).on('click', ".tabs-holder li:not('.disabled') a ", function(e){
		var currentAttrValue= $(this).attr('href');
		var currentId = $(this).parent().attr('id');
		
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		if($('.serialField').length) $('.serialField').focus();
		setCookie(cookieName, currentId);
		
		e.preventDefault();
	});
	
	
	// Търсене по баркод
	$(document.body).on('click', "#searchBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var searchVal = $("input[name=searchBarcode]").val();
		if(!searchVal) {
			e.preventDefault();
			return;
		}
		resObj = new Object();
		resObj['url'] = url;
		
		var data = {search:searchVal};
		getEfae().process(resObj, data);
	});
	
	// При клик на полето за баркод да се отваря приложение
	$(document.body).on('click', ".scanElement", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		$(location).attr('href', url);
	});
}

// Кой таб да е активен
function render_activateTab(data)
{
	if(data.tabId){
		$("#" + data.tabId).removeClass('disabled');
		$("#" + data.tabId).addClass('active')
		$("#" + data.tabId).siblings().removeClass('active');
		
		var currentAttrValue= $("#" + data.tabId + " a").attr('href');
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		
		var cookieId = $("#nameHolder").attr("data-id");
		var cookieName = 'terminalTab' + cookieId;
		setCookie(cookieName,  data.tabId);
		return;
	}
}

function render_prepareKeyboard()
{
	$('.nmpd-wrapper').remove();

	setTimeout(function(){
		$('#numPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
			target: $('.quantityField')
		});
		$('#weightPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
			target: $('.weightField')
		});
		$('#serialPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
			target: $('.serialField')
		});
	}, 500);
}


/**
 * Създава бисквитка
 */
function setCookie(key, value) {
	var expires = new Date();
	expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
	document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}


/**
 * Чете информацията от дадена бисквитка
 */
function getCookie(key) {
	var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
	return keyValue ? keyValue[2] : null;
}



function disableScale() {
	if (isTouchDevice()) {
		$('meta[name=viewport]').remove();
		$('head').append('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">');
	}
}
