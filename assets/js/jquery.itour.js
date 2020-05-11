/*
 * jQuery itour v 3.2.4
 *
 * Copyright 2015, Linnik Yura | LI MASS CODE | http://masscode.ru
 *
 * Last Update 03.09.2018
 */

(function ($) {
	"use strict";	
	var methods = {
		init: function (options) {
			var p = {
				CSSClass:'anyClassName',				//Assign for tour a unique class name to change the display styles of the tour.
				tourID:'anyTourID',						//This string allows you to save data with a unique name about  tour progress. It can be used to save information on the progress of the tour for several users. Or save the progress of each tour separately
				introShow:false,						//If set to true, before the tour you will see the introductory slide, which will offer to see a tour.
				introCover:false,						//Path to the cover of tour
				startStep:1,							//Step from which the tour begins
				tourMapEnable:true,						//Tour Map Enable
				tourMapPos:'right',						//Tour Map Position 
				tourMapJump:true,						//If set to false, then links of steps on the tour map will not be clickable
				tourTitle:'Tour Title Default',			//Tour title
				tourMapVisible:false,					//Specifies to show or hide the map of the tour at the start of the tour
				spacing:10,								//Indent highlighting around the element, px
				overlayClickable:true,					//This parameter enables or disables the click event for overlying layer
				modalCancelVisible:false,				//Shows a cancel button in modal window.
				stepNumbersVisible:true,				//Shows the total number of steps and the current step number
				showAbsentElement:false,				//Shows an absent element in tour map and find this element in DOM.
				tourContinue:true,						//This parameter add the ability to continue the unfinished tour.
				textDirection:'ltr',					//The direction property specifies the text direction/writing direction. (ltr, rtl)
				steps:[{
					image:'',							//Path to image file
					title:'New Step Title',				//Name of step
					content:'New Step Description',		//Description of step
					contentPosition:'auto',				//Position of message
					name:'uniqueName',					//Unique Name (<div data-name="uniqueName"></div>) of highlighted element or .className (<div class="className"></div>) or #idValue (<div id="idValue"></div>)
					disable:false,						//Block access to element
					overlayOpacity:0.5,					//For each step, you can specify the different opacity values of the overlay layer.
					event:'next',						//An event that you need to do to go to the next step
					skip: false,						//Step can be skipped if you set parameter "skip" to true.
					nextText:'Next',					//The text in the Next Button
					prevText:'Prev',					//The text in the Prev Button
					trigger:false,						//An event which is generated on the selected element, in the transition from step to step
					stepID:'',							//Unique ID Name. This name is assigned to the "html" tag as "data-step" attribute (If not specified, the plugin generates it automatically in the form: "step-N")
					loc:false,							//The path to the page on which the step should work
					before:function(){},				//Triggered before the start of step
					during:function(){},				//Triggered after the onset of step
					after:function(){},					//Triggered After completion of the step, but before proceeding to the next
					delayBefore:0,						//The delay before the element search, ms
					delayAfter:0,						//The delay before the transition to the next step, ms
					checkNext:{							//Function in which you can carry out any verification by clicking on the "Next" button. 
						func:function(){return true},	////If the function returns True, the step will be switched.
						messageError:'Fulfill all the conditions!'	//If the function returns "False", an error message will appear in the message window
					},
					checkPrev:{							//Function in which you can carry out any verification by clicking on the "Prev" button. 
						func:function(){return true},
						messageError:'Fulfill all the conditions!'	
					}
				}],
				create: function(){},					//Triggered when the itour is created
				end: function(){},						//Triggered when the tour ended, or was interrupted
				abort: function(){},					//Triggered when the tour aborted
				finish: function(){},					//Triggered when step sequence is over
				lang: {									//Default language settings
					cancelText:	'Cancel Tour',			//The text in the cancel tour button
					hideText: 'Hide Tour Map',			//The text in the hidden tour map button 
					tourMapText:'•••',					//The text in the show tour button
					tourMapTitle: 'Tour Map',			//Title of Tour map button
					nextTextDefault:'Next',				//The text in the Next Button
					prevTextDefault:'Prev',				//The text in the Prev Button
					endText:'End Tour',
					contDialogTitle:'Continue the unfinished tour?',										//Title of continue dialog
					contDialogContent:'Click "Continue" to start with step on which finished last time.',	//Content of continue dialog
					contDialogBtnBegin:'Start from beginning',												//Text in the start button of continue dialog 
					contDialogBtnContinue:'Continue',														//Text in the continue button of continue dialog 
					introTitle:'Welcome to the interactive tour', 											//Title of introduction dialog
					introContent:'This tour will tell you about the main site functionalities',				//Content of introduction dialog
					introDialogBtnStart:'Start',															//Text in the start button of introduction dialog
					introDialogBtnCancel:'Cancel'															//Text in the cancel button of introduction dialog
				}
			};
			return this.each(function (e) {
				/**/
				/*detect IE brouser*/
				/**/
				var isIE = /*@cc_on!@*/false || document.documentMode; 
				if(isIE){
					$('html').addClass('bms');	
				}			

				/**/
				/*creating base object*/
				/**/
				var hWrap = $('body');
				if(!hWrap.data().itour){
					hWrap.data().itour = {};
				}
				if(!hWrap.data().itour.ini){
					
					hWrap.addClass('hWrap');

					/**/
					/*createt base data object*/
					/**/
					var hData = hWrap.data().itour;
					
					/**/
					/*save all options in base data object*/
					/**/				
					hData.opt = {};
					if (options) {
						$.extend(p.lang, options.lang);
					}
					$.extend(hData.opt, p);	
					if (options) {
						$.extend(hData.opt, options);	
					}
					$.extend(hData.opt.lang, p.lang);	

					if(hWrap.data().itourLang){
						$.extend(hData.opt.lang, hWrap.data().itourLang);
					}
					
					/**/
					/*save user options*/
					/**/
					hData.userOpt = {};
					if (options) {
						$.extend(hData.userOpt, options);
					}
					
					/**/
					/*creating object for addition vars*/
					/**/
					hData.v = {};
					
					/**/
					/*Creating tour map block*/
					/**/
					hData.v.hNavPos = $('<div class="hNavPos hNavPos-'+hData.opt.tourMapPos+'"></div>').appendTo('body');
					
					if(hData.opt.tourMapEnable === false || hData.opt.tourMapEnable === 'false'){
						$('html').addClass('hNav-disable');
					}
					
					
					var oddDetect = function(){
						if($(window).height() & 1){
							$('html').addClass('vh-odd');
						}else{
							$('html').removeClass('vh-odd');	
						}
						if($(window).width() & 1){
							$('html').addClass('vw-odd');
						}else{
							$('html').removeClass('vw-odd');	
						}
					};
					oddDetect();
					
					
					
					if(hData.opt.textDirection == "rtl"){
						hData.v.hNavPos.addClass('rtl');
					}
					
					/**/
					/*Any Tour ID*/
					/**/
					hData.v.tourid = '';
					if(hData.opt.tourTitle){
						hData.v.tourid = hData.opt.tourTitle;
						hData.v.hNavHeader = $('<div class="hNavHeader">'+hData.opt.tourTitle+'</div>').prependTo(hData.v.hNavPos);
					}
					hData.v.hNavWrap = $('<div class="hNavWrap"></div>').appendTo(hData.v.hNavPos);
					hData.v.hNavAction = $('<div class="hNavAction"></div>').appendTo(hData.v.hNavPos);
					
					/**/
					/*save start state of style*/
					/**/
					hData.v.startStyle = hWrap.attr('style') || false;
					
					/**/
					/*Step can be skipped if you set parameter "skip" to true.*/
					/**/
					var actualStepsCombine = [];
					var actualIndex = 0;
					
					var uid = 0;
					var loc = location.href;
					var stepReplace = function(hStepItem){
						
						if(!hStepItem.loc){
							hStepItem.loc = loc;
						}
						actualStepsCombine.push(hStepItem);

						/**/
						/*Creating step links*/
						/**/
						var hStepItemTitle = hStepItem.title ? hStepItem.title : '№'+(actualIndex+1);
						var hindexStep = $('<div data-hindex="'+actualIndex+'" class="hStepItem">'+hStepItemTitle+'</div>').appendTo(hData.v.hNavWrap);
						if(hStepItem.ready){
							hindexStep.addClass('hSuccess');	
						}
						actualIndex++;
					};
					
					
					var escapeRe = function (value) {
						return value.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, "\\$&")
					};
					
					

					for(var i = 0; i < hData.opt.steps.length; i++){
						var skip = hData.opt.steps[i].skip;
						if(!skip){

							var hStepItem = hData.opt.steps[i];
							
							
							if(hData.opt.showAbsentElement || hStepItem.contentPosition == 'center'){
								stepReplace(hStepItem);
							}else{
								var targetElement;
								if($(hStepItem.name).length){
									targetElement = $(hStepItem.name); 		
								}else{
									targetElement = $('[data-name *="'+hStepItem.name+'"]');
								}	
								if(targetElement.length){
									stepReplace(hStepItem);
								}
							}
						}
						hData.v.tourid = hData.v.tourid + hData.opt.steps[i].name;
					}
					hData.opt.steps = actualStepsCombine;

					hData.opt.spacing = parseFloat(hData.opt.spacing);

					/**/
					/*Save Desktop Events $ Save Touch Events*/
					/**/
					var iOs = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
					var clickEvent = iOs ? 'touchend' : 'click';
					var touchMoving = false;
					if (iOs){  
						document.ontouchmove = function(e){
							touchMoving = true;
						}
						document.ontouchend = function(e){
							setTimeout(function(){
								touchMoving = false;
							},100);
						}
					} 
					var mousemoveEvent = 'mousemove.'+hData.hName;
					var mousedownEvent = 'mousedown.'+hData.hName;
					var mouseupEvent = 'mouseup.'+hData.hName;
					if('ontouchstart' in window){
						mousemoveEvent = 'touchmove.'+hData.hName;
						mousedownEvent = 'touchstart.'+hData.hName;
						mouseupEvent = 'touchend.'+hData.hName;
					}	
					hData.v.mousemoveEvent = mousemoveEvent;
					hData.v.mouseupEvent = mouseupEvent;
					hData.v.mousedownEvent = mousedownEvent;
					
					/**/
					/*Add uniquee CSS Class to HTML tag*/
					/**/
					if(hData.opt.CSSClass){
						$('html').addClass(hData.opt.CSSClass);	
					}
	
					/**/
					/*Overlay Elements*/
					/**/
					var hOverlayDisable = $('<div class="hOverlayDisable"></div>');
					var hOverlayBottom = $('<div class="hOverlay hOverlayBottom"></div>');
					var hOverlayTop = $('<div class="hOverlay hOverlayTop"></div>');
					var hOverlayRight = $('<div class="hOverlay hOverlayRight"></div>');
					var hOverlayLeft = $('<div class="hOverlay hOverlayLeft"></div>');
					hData.v.overlays = hOverlayLeft.add(hOverlayRight).add(hOverlayTop).add(hOverlayBottom).appendTo('body');
					hData.v.hOverlayDisable = hOverlayDisable.appendTo('body');
					
					/**/
					/*Detect Privacy Mode in Safari (iOS)*/
					/**/
					try { localStorage.test = 2; } catch (e) {
						alert('You are in Privacy Mode\nPlease deactivate Privacy Mode and then reload the page to view the Tour.');
						hWrap.itour('destroy');
						return false;
					}
					
					/**/
					/*This function converts the parameters of the object to a string*/
					/**/
					var toStringObjIndex = 0;
					var typeDetect = function(per){
						if(typeof per == 'function'){
							//function
							return typeof per;
						}
						if(typeof per == 'boolean'){
							//boolean
							return typeof per;
						}
						if(typeof per == 'string'){
							//string
							return typeof per;
						}
						if(typeof per == 'number'){
							//number
							return typeof per;
						}
						if(typeof per == 'object'){
							if(Array.isArray(per)){
								//Array
								return 'array';
							}else{
								//Object
								return 'object';
							}
						}
					};
					var toStringObj = function(obj){
						var newIndex = toStringObjIndex++;
						var objEnter = obj;
						var tempObj = {};
						tempObj[newIndex] = {};
						
						for (var i in objEnter) {
							var key1 = i;
							var val = objEnter[key1];
							//function
							if(typeDetect(val) == 'function'){
								tempObj[newIndex][key1] = val.toString();
							}
							//array
							if(typeDetect(val) == 'array'){
								var valEach = val;
								for(var f = 0; f < valEach.length; f++){
									if(typeDetect(valEach[f]) == 'object'){
										var objMass = valEach[f];
										var val3 = toStringObj(objMass);
										valEach[f] = val3;	
									}		
								}
								tempObj[newIndex][key1] = valEach;
							}
							//object
							if(typeDetect(val) == 'object'){
								var val2 = toStringObj(val);	
								tempObj[newIndex][key1] = val2;
							}
							//string, number, boolean
							if(typeDetect(val) == 'string' || typeDetect(val) == 'number' || typeDetect(val) == 'boolean'){
								tempObj[newIndex][key1] = objEnter[key1];
							}
						}
						return tempObj[newIndex];	
					};
	
					/**/
					/*Overlay Elements Positioning*/
					/**/
					var overlayPos = function(targetEl){
						
						if(targetEl && targetEl.length){
							var targetElWidth = Math.round(targetEl.outerWidth());
							var targetElHeight = Math.round(targetEl.outerHeight());
							var targetElTop = Math.round(targetEl.offset().top);
							var targetElLeft = Math.round(targetEl.offset().left);
							
							if(targetEl.is('body')){
								var wHalf = Math.round($(window).width()/2);
								var hHalf = Math.round($(window).height()/2);
								
								hOverlayTop.css({
									left:0,
									top:0,
									width:wHalf,
									height:hHalf
								});
								
								hOverlayRight.css({
									left:wHalf,
									top:0,
									width:($(window).width() - wHalf),
									height:hHalf
								});
								
								hOverlayLeft.css({
									left:0,
									top:hHalf,
									width:wHalf,
									height:($(window).height() - hHalf)
								});
								
								hOverlayBottom.css({
									left:wHalf,
									top:hHalf,
									width:($(window).width() - wHalf),
									height:($(window).height() - hHalf)
								});
							}else{
								$('.itour-highlight').removeClass('itour-highlight');
								targetEl.addClass('itour-highlight');
								
								hOverlayBottom.css({
									left:((targetElLeft - hData.opt.spacing) - $(window).scrollLeft())+'px',
									top:((targetElTop + hData.opt.spacing + targetElHeight) - $(window).scrollTop())+'px',
									width:(($(window).width() - (targetElLeft - hData.opt.spacing)) + $(window).scrollLeft())+'px',
									height:(($(window).height() - (targetElTop + hData.opt.spacing + targetElHeight))+$(window).scrollTop())+'px'
								});
								hOverlayTop.css({
									left:0,
									top:0,
									width:((((targetElLeft + hData.opt.spacing) + targetElWidth)) - $(window).scrollLeft())+'px',
									height:((targetElTop - hData.opt.spacing) - $(window).scrollTop())+'px'
								});
								hOverlayRight.css({
									left:((targetElLeft + hData.opt.spacing + targetElWidth) - $(window).scrollLeft())+'px',
									top:0,
									width:(($(window).width() - (targetElLeft + hData.opt.spacing + targetElWidth))+$(window).scrollLeft())+'px',
									height:((targetElTop + hData.opt.spacing + targetElHeight)- $(window).scrollTop())+'px'
								});

								hOverlayLeft.css({
									left:(0 - $(window).scrollLeft())+'px',
									top:((targetElTop - hData.opt.spacing) - $(window).scrollTop())+'px',
									width:((targetElLeft - hData.opt.spacing))+'px',
									height:(($(window).height() - (targetElTop - hData.opt.spacing)) + $(window).scrollTop())+'px'
								});
							}
							
							setTimeout(function(){
								hData.v.overlays.addClass('noTransition');
							},310);
							
							/**/
							/*Specifies the data for the starting step*/
							/**/
							var targetOpt = hData.opt.steps[hData.v.startIndex];
							var contentPos = $.trim(targetOpt.contentPosition).split('');
							var top;
							var left;
							var blockTransLeft = '0';
							var blockTransTop = '0';
							var blockLeft;
							var blockTop;
							var defOpacity = 0.5;
							
							if(hData.opt.steps){
								if(hData.opt.steps[hData.v.startIndex].disable){
									hOverlayDisable.addClass('hOverlayDisableTrue');
								}else{
									hOverlayDisable.removeClass('hOverlayDisableTrue');	
								}
								if(hData.opt.steps[hData.v.startIndex].overlayOpacity >= 0){
									defOpacity = hData.opt.steps[hData.v.startIndex].overlayOpacity;
								}
								
								$(document).on('keyup.itour',function(e){
									if(parseFloat(e.keyCode) == 27){
										hWrap.itour('destroy');
										if (hData.opt.end !== undefined) {hData.opt.end();}	
										if (hData.opt.abort !== undefined) {hData.opt.abort();}		
									}
								});
								
								hData.v.overlays.css({opacity:defOpacity});
								/**/
								/*Positioning in center of the screen */
								/**/
								if(hData.opt.steps[hData.v.startIndex].contentPosition == 'center'){
									contentPos = ['c','c','c'];
									left = '50%';
									top = '50%';
								}else{

									/**/
									/*Automatic positioning of message */
									/**/
									if(contentPos.length != 3 || contentPos[0] != 't' && contentPos[0] != 'r' && contentPos[0] != 'b' && contentPos[0] != 'l'){
										hData.opt.steps[hData.v.startIndex].contentPosition = 'auto';
									}
									if(hData.opt.steps[hData.v.startIndex].contentPosition == 'auto'){
										var targetPosTop = (targetElTop - $(window).scrollTop());
										var maxSpace = targetPosTop;
										contentPos = ['t','c','c'];
										top = ((targetElTop - (hData.opt.spacing + 15)) - $(window).scrollTop());
										left = (targetElLeft + targetElWidth/2) - $(window).scrollLeft();
										var targetElBottom = ($(window).height() - (targetPosTop + targetElHeight));
										if(targetElBottom > maxSpace){
											maxSpace = targetElBottom;
											contentPos = ['b','c','c'];
											top = (targetElTop + hData.opt.spacing + targetElHeight + 15) - $(window).scrollTop();
										}
										var targetPosLeft = (targetElLeft - $(window).scrollLeft());
										if(targetPosLeft > maxSpace){
											maxSpace = targetPosLeft;
											contentPos = ['l','c','c'];
											left = (targetElLeft - (hData.opt.spacing + 15)) - $(window).scrollLeft();
											top = (targetElTop + targetElHeight/2) - $(window).scrollTop();
										}
										var targetElRight = ($(window).width() - (targetPosLeft + targetElWidth));
										if(targetElRight > maxSpace){
											maxSpace = targetElRight;
											contentPos = ['r','c','c'];
											left = (targetElLeft + hData.opt.spacing + targetElWidth + 15) - $(window).scrollLeft();
											top = (targetElTop + targetElHeight/2) - $(window).scrollTop();
										}
										if(contentPos[0] == 'l' || contentPos[0] == 'r'){
											if((targetPosTop - targetElBottom) > 100 || (targetPosTop - targetElBottom) < -100){
												if(targetPosTop > targetElBottom){
													contentPos[1] = 'c';
													contentPos[2] = 't';
													top = (targetElTop + targetElHeight/2) - $(window).scrollTop();
													
													/**/
													/*If the message is hidden behind the bottom of the screen*/
													/**/
													if((top + 50) > ($(window).scrollTop() + $(window).height())){
														contentPos[1] = 't';
														top = (targetElTop - hData.opt.spacing) - $(window).scrollTop();
													}
												}
												if(targetPosTop < targetElBottom){
													contentPos[1] = 'c';
													contentPos[2] = 'b';
													top = (targetElTop + targetElHeight/2) - $(window).scrollTop();	
													
													/**/
													/*If the message is hidden behind the top of the screen*/
													/**/
													if((top - 50) < $(window).scrollTop()){
														contentPos[1] = 'b';
														top = (targetElTop + targetElHeight + hData.opt.spacing) - $(window).scrollTop();
													}
												}
											}else{
												contentPos[1] = 'c';
												contentPos[2] = 'c';	
												top = (targetElTop + targetElHeight/2) - $(window).scrollTop();	
											}
										}
										if(contentPos[0] == 't' || contentPos[0] == 'b'){
											if((targetPosLeft - targetElRight) > 100 || (targetPosLeft - targetElRight) < -100){						
												if(targetPosLeft > targetElRight){
													contentPos[1] = 'c';
													contentPos[2] = 'l';
													left = (targetElLeft + targetElWidth/2) - $(window).scrollLeft();	
												}
												if(targetPosLeft < targetElRight){
													contentPos[1] = 'c';
													contentPos[2] = 'r';
													left = (targetElLeft + targetElWidth/2) - $(window).scrollLeft();
												}
											}else{
												contentPos[1] = 'c';
												contentPos[2] = 'c';	
												left = (targetElLeft + targetElWidth/2) - $(window).scrollLeft();
											}
										}
									}else{
		
										/**/
										/*Decoding of first symbol*/
										/**/
										if(contentPos[0] == 'r'){
											left = (targetElLeft + hData.opt.spacing + targetElWidth + 15) - $(window).scrollLeft();
											blockTransLeft = '0';
											blockLeft = '0';
										}
										if(contentPos[0] == 'l'){
											left = (targetElLeft - (hData.opt.spacing + 15)) - $(window).scrollLeft();
											blockTransLeft = '-100%';
											blockLeft = '0';
										}
										if(contentPos[0] == 'b'){
											top = ((targetElTop + hData.opt.spacing + targetElHeight) - $(window).scrollTop()) + 15;
											blockTransTop = '0';
											blockTop = '0';
										}
										if(contentPos[0] == 't'){
											top = ((targetElTop - (hData.opt.spacing + 15)) - $(window).scrollTop());
											blockTransTop = '-100%';
											blockTop = '0';
										}
		
										/**/
										/*Decoding of second symbol*/
										/**/
										if(contentPos[1] == 'c' && contentPos[0] == 'r' || contentPos[1] == 'c' && contentPos[0] == 'l'){
											top = (targetElTop + targetElHeight/2) - $(window).scrollTop();
										}
										if(contentPos[1] == 'b'){
											top = (targetElTop + targetElHeight + hData.opt.spacing) - $(window).scrollTop();
										}
										if(contentPos[1] == 't'){
											top = (targetElTop - hData.opt.spacing) - $(window).scrollTop();
										}
										if(contentPos[1] == 'c' && contentPos[0] == 'b' || contentPos[1] == 'c' && contentPos[0] == 't'){
											left = (targetElLeft + targetElWidth/2) - $(window).scrollLeft();
										}
										if(contentPos[1] == 'l'){
											left = (targetElLeft - hData.opt.spacing) - $(window).scrollLeft();
										}
										if(contentPos[1] == 'r'){
											left = (targetElLeft + targetElWidth + hData.opt.spacing) - $(window).scrollLeft();
										}
									}
								
								}
								
								/**/
								/*Set of the position code*/
								/**/
								hData.v.hContPos.css({left:left,top:top}).attr({
									'data-pos':contentPos[0],
									'data-cone':contentPos[1],
									'data-cont':contentPos[2]
								});

								hData.v.hContPos.stop(true).css({opacity:0}).show();
								
								/**/
								/*Position correction*/
								/**/
								if(contentPos[0] == 't' || contentPos[0] == 'b'){
									
									hData.v.hContBlock.removeAttr('style');	
									
									var ww = $(window).width();
									var sum = (hData.v.hContBlock.offset().left + hData.v.hContBlock.width());
									if(sum > (ww - 5)){
										hData.v.hContBlock.css({marginLeft:-((sum - ww)+5)});
									}
									
									
									
									if(hData.v.hContBlock.offset().left < 5){
										
										console.log((hData.v.hContBlock.offset().left*-1+5))
										
										
										hData.v.hContBlock.css({marginLeft:(hData.v.hContBlock.offset().left*-1+5)})	
									}
								}
								
								if(contentPos[0] == 'l' || contentPos[0] == 'r'){
									
									hData.v.hContBlock.removeAttr('style');	
									
									var ww = $(window).width();
									var sum = (hData.v.hContBlock.offset().left + hData.v.hContBlock.width());
									if(sum > (ww - 5)){
										hData.v.hContBlock.css({width:hData.v.hContBlock.width() - ((sum - ww)+5)});
									}
									
									if(hData.v.hContBlock.offset().left < 5){
										hData.v.hContBlock.css({width:hData.v.hContBlock.width() - (hData.v.hContBlock.offset().left*-1+5)})	
									}
									
								}
								
								if(contentPos[0] == 'c' & contentPos[1] == 'c' & contentPos[2] == 'c'){
									hData.v.hContBlock.removeAttr('style');	
								}

								/*Show Message Window*/
								hData.v.hContPos.stop(true).delay(500).animate({opacity:1});
							}else{
								console.log('Parameter "steps" is undefined or empty');	
							}
						}
					};
					hData.v.overlayPos = overlayPos;
					
					/**/
					/*Creating message block*/
					/**/
					hData.v.hContPos = $('<div>').addClass('hContPos').appendTo('body');
					hData.v.hContBlock = $('<div>').addClass('hContBlock').appendTo(hData.v.hContPos);
					hData.v.hContBody = $('<div>').addClass('hContBody').appendTo(hData.v.hContBlock);
					hData.v.hContFooter = $('<div>').addClass('hContFooter').appendTo(hData.v.hContBlock);
					hData.v.hContHeader = $('<div>').addClass('hContHeader').prependTo(hData.v.hContBlock);
					
					if(hData.opt.textDirection == "rtl"){
						hData.v.hContBlock.addClass('rtl');
					}	

					hData.v.hAction = $('<div>').addClass('hAction').prependTo(hData.v.hContBlock);
					if(hData.opt.modalCancelVisible){
						hData.v.hCancelBtn = $('<span class="hCancelBtn hClose hRoundBtn" title="'+hData.opt.lang.cancelText+'">×</span>').appendTo(hData.v.hAction);
					}
					hData.v.hNavBtn = $('<span class="hNavBtn hRoundBtn" title="'+hData.opt.lang.tourMapTitle+'">'+hData.opt.lang.tourMapText+'</span>')
					if(hData.opt.tourMapEnable === true || hData.opt.tourMapEnable === 'true'){
						hData.v.hNavBtn.appendTo(hData.v.hAction);
					}

					hData.v.hContImage = $('<div class="hContImage"></div>').prependTo(hData.v.hContBlock);
					
					hData.v.stepNumbersVisible = $('<div class="hStepNumbers"></div>');
					
					$('<div class="hStepNumber"></div>').appendTo(hData.v.stepNumbersVisible);
					$('<div class="hStepTotal">'+hData.opt.steps.length+'</div>').appendTo(hData.v.stepNumbersVisible);
					
					/**/
					/*This function skip an absent element*/
					/**/
					var searchElement = function(mess){
						if((parseFloat(hData.v.startIndex) + 1) < hData.opt.steps.length){
							if(hData.opt.showAbsentElement){
								$('[data-hindex="'+hData.v.startIndex+'"]').addClass('hElAbsent');
							}
							if(hData.v.event === 'next'){
								hData.v.startIndex++;
							}else{
								hData.v.startIndex--;	
							}
							if(!hData.v.event){
								hData.v.event = 'next';
							}
							hStep();	
						}else{
							console.log('Target is '+mess);
							hWrap.itour('destroy');
						}
					}

					/**/
					/*Creating the tour map buttons*/					
					/**/
					hData.v.hClose = $('<span class="hClose hBtn">'+hData.opt.lang.cancelText+'</span>').appendTo(hData.v.hNavAction);
					hData.v.hHide = $('<span class="hHide hBtn">'+hData.opt.lang.hideText+'</span>').appendTo(hData.v.hNavAction);
					
					/**/
					/*Changing the content of message block*/
					/**/
					hData.opt.startStep = parseFloat(hData.opt.startStep);
					if(hData.opt.startStep < 1) {
						hData.opt.startStep == 1;
					}

					var afterFunc = function(){
						if (hData.opt.steps[hData.v.startIndex].after !== undefined) {hData.opt.steps[hData.v.startIndex].after();}
					};
					
					var messageErrorNext = function(){
						var mess = hData.opt.lang.messageNextError;
						if(hData.opt.steps[hData.v.startIndex].checkNext.messageError && $.trim(hData.opt.steps[hData.v.startIndex].checkNext.messageError) !== ''){
							mess = hData.opt.steps[hData.v.startIndex].checkNext.messageError;
						}
						var errorMessage = $('<div>').addClass('hErrorMessage').text(mess);
						$('.hErrorMessage').remove();
						$('.hContFooter').after(errorMessage);
						errorMessage.animate({opacity:0.7},1000);
						setTimeout(function(){
							errorMessage.stop(true).animate({opacity:0},function(){
								errorMessage.animate({height:0, padding:0},function(){
									errorMessage.remove()	
								});
							})
						},3000)
					};
					
					var messageErrorPrev = function(){
						var mess = hData.opt.lang.messagePrevError;
						if(hData.opt.steps[hData.v.startIndex].checkPrev.messageError && $.trim(hData.opt.steps[hData.v.startIndex].checkPrev.messageError) !== ''){
							mess = hData.opt.steps[hData.v.startIndex].checkPrev.messageError;
						}
						var errorMessage = $('<div>').addClass('hErrorMessage').text(mess);
						$('.hErrorMessage').remove();
						$('.hContFooter').after(errorMessage);
						errorMessage.animate({opacity:0.7},1000);
						setTimeout(function(){
							errorMessage.stop(true).animate({opacity:0},function(){
								errorMessage.animate({height:0, padding:0},function(){
									errorMessage.remove()	
								});
							})
						},3000)
					};
					
					var checkNextFunc = function(){
						var result = true;
						if(hData.opt.steps[hData.v.startIndex].checkNext){
							if (hData.opt.steps[hData.v.startIndex].checkNext.func !== undefined){
								result = hData.opt.steps[hData.v.startIndex].checkNext.func();
							}
						}
						return result;
					};
					
					var checkPrevFunc = function(){
						var result = true;
						if(hData.opt.steps[hData.v.startIndex].checkPrev){
							if (hData.opt.steps[hData.v.startIndex].checkPrev.func !== undefined){
								result = hData.opt.steps[hData.v.startIndex].checkPrev.func();
							}
						}
						return result;
					};

					var hStep = function(){
						if(hData.v.overlays){
							hData.v.overlays.removeClass('noTransition');
						}
						hData.v.overlayEnable = false;
						setTimeout(function(){
							hData.v.overlayEnable = true;
						},1000);
						
						hData.v.hContPos.stop(true).fadeOut(100,function(){
							var changeDelay = 300;
							if(hWrap.data().itour_go){
								changeDelay = 1;	
								localStorage.removeItem('itour_go');
								hWrap.data().itour_go = false;
							}

							setTimeout(function(){	
								
								/**/
								/*Cleaning the content of message block*/
								/**/
								hData.v.hContPos.hide();
								hData.v.hContHeader.empty();
								hData.v.hContBody.empty();
								hData.v.hContFooter.empty();
								
								/**/
								/*If listed steps in parameters*/
								/**/
								if(hData.opt.steps){
									
									var targetOpt = hData.opt.steps[hData.v.startIndex];
									if(targetOpt){
										if (targetOpt.before !== undefined) {targetOpt.before();}
										
										/**/
										/*The delay before the element search*/
										/**/
										setTimeout(function(){										
											var targetElement;
											if($(targetOpt.name).length){
												targetElement = $(targetOpt.name); 		
											}else{
												targetElement = $('[data-name *="'+targetOpt.name+'"]')		
											}
											hData.v.targetEl = targetElement;
											
											/**/
											/*If the specified element (purpose) for this step*/
											/**/
											if(targetOpt.contentPosition == 'center'){
												hData.v.targetEl = $('body');
											}

											if(hData.v.targetEl.length){
												if(hData.v.targetEl.is(':visible')){
													
													var myFunc = function(){
														
														$('[data-hindex="'+hData.v.startIndex+'"]').removeClass('hElAbsent');
														$('[data-hindex="'+hData.v.startIndex+'"]').removeClass('hElHidden');
														
														/**/
														/*Transmits the step data to the overlay function*/
														/**/

														$('html').removeAttr('data-step');
														var stepID = 'step-'+(hData.v.startIndex + 1);
														if(targetOpt.stepID){
															stepID = targetOpt.stepID;
														}
														$('html').attr('data-step',stepID);
														
														hData.v.overlayPos(hData.v.targetEl);
														var targetOptTitle = targetOpt.title ? targetOpt.title : '№'+(parseFloat(hData.v.startIndex)+1);
														
														/**/
														/*Insert new content*/
														/**/
														if(targetOpt.image && $.trim(targetOpt.image) !== ''){
															$('<img>')
															.attr('src',targetOpt.image)
															.on('load',function(){
																hData.v.hContImage.html('<img src="'+targetOpt.image+'">');
																setTimeout(function(){
																	var image = hData.v.hContImage.find('img');
																	var coverHeight = image.height().toFixed();
																	if(coverHeight & 1){
																		coverHeight = (parseFloat(coverHeight)+1);
																	}
																	image.height(coverHeight);
																},500)
															});
														}else{
															hData.v.hContImage.empty();	
														}
														if(targetOpt.title){
															hData.v.hContHeader.html(targetOptTitle);	
														}else{
															hData.v.hContHeader.html('');	
														}
														
														hData.v.hContBody.html(targetOpt.content);
	
														/**/
														/*Sets the text to the default buttons*/
														/**/
														if(!hData.opt.steps[hData.v.startIndex].prevText) {hData.opt.steps[hData.v.startIndex].prevText = hData.opt.lang.prevTextDefault;}
														if(!hData.opt.steps[hData.v.startIndex].nextText) {hData.opt.steps[hData.v.startIndex].nextText = (parseFloat(hData.v.startIndex)+1) == hData.opt.steps.length ? hData.opt.lang.endText : hData.opt.lang.nextTextDefault;}
														
														/**/
														/*Insert step numbers*/
														/**/
														var stepValue = parseFloat(hData.v.startIndex)+1;
														$('.hStepNumber',hData.v.stepNumbersVisible).html(stepValue);
														if(hData.opt.stepNumbersVisible){
															hData.v.stepNumbersVisible.appendTo(hData.v.hContFooter);
														}
														
														/**/
														/*Save step numbers in localStorage*/
														/**/
														if (typeof(Storage) !== "undefined"){
															var itour_data = {};
															if(stepValue > 1){
																itour_data = {
																	tourId:hData.v.tourid,
																	stepValue:stepValue,
																	page:location.href
																};
															}else{
																itour_data = {
																	tourId:false,
																	stepValue:stepValue,
																	page:location.href
																};	
															}
															localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
														}
														
														/**/
														/*Insert buttons*/
														/**/
														//hData.v.hClose.clone().addClass('hBtnRight').appendTo(hData.v.hContFooter);
														var prevIndex = hData.v.startIndex-1;
														if(hData.opt.steps[prevIndex]){
															$('<span class="hBtn hPrev">'+hData.opt.steps[hData.v.startIndex].prevText+'</span>').appendTo(hData.v.hContFooter);
														}
														var hNext;
														if(hData.opt.steps[hData.v.startIndex]){
															if(hData.opt.steps[hData.v.startIndex].ready){
																hNext = $('<span class="hBtn hNext">'+hData.opt.steps[hData.v.startIndex].nextText+'</span>').appendTo(hData.v.hContFooter);
															}
														}
														
														if(!hData.opt.steps[hData.v.startIndex].event){
															hData.opt.steps[hData.v.startIndex].event = 'next';
														}
														
														hData.v.customEvent = false;
														hData.v.customTarget = false;
														
														if(hData.opt.steps[hData.v.startIndex].event == 'next'){
															if(!$(hNext).length){
																hNext = $('<span class="hBtn hNext">'+hData.opt.steps[hData.v.startIndex].nextText+'</span>').appendTo(hData.v.hContFooter);
															}
														}else{
	
															if(typeof hData.opt.steps[hData.v.startIndex].event == 'object'){
																hData.v.customEvent = hData.opt.steps[hData.v.startIndex].event[0];
																hData.v.customTarget = hData.opt.steps[hData.v.startIndex].event[1];
															}
	
															if(typeof hData.opt.steps[hData.v.startIndex].event == 'string'){
																hData.v.customEvent = hData.opt.steps[hData.v.startIndex].event;
																hData.v.customTarget = hData.v.targetEl;
															}
	
															if(hData.v.customEvent){
																if(hData.v.customTarget){
																	hData.v.customTarget.attr('data-uid','h-'+uid++);
																	hData.v.customTargetSelector = '[data-uid="'+hData.v.customTarget.attr('data-uid')+'"]';
																	if(hData.v.customTargetSelector){
																		$(document).on(hData.v.customEvent+'.itour',hData.v.customTargetSelector,function(e){
																			if(checkNextFunc() === true){
																				$(this).off(e);
																				targetOpt.ready = true;
																				
																				/**/
																				/*Step is marked as completed (painted over)*/
																				/**/
																				$('[data-hindex="'+hData.v.startIndex+'"]').addClass('hSuccess');
																				afterFunc();
																				setTimeout(function(){
																					hData.v.startIndex++;
																					hStep();	
																				},targetOpt.delayAfter);
																			}else{
																				messageErrorNext();
																			}
																		});	
																	}else{
																		console.log('Custom target don\'t have class and id attributes');
																	}
																}else{
																	console.log('Custom target is absent');
																}
															}else{
																console.log('Custom event is absent');
															}
														}
	
														/**/
														/*Highlighting the active step*/
														/**/
														$('[data-hindex]').removeClass('hCur').filter('[data-hindex="'+hData.v.startIndex+'"]').addClass('hCur');
														if(hData.opt.steps[hData.v.startIndex].trigger && hData.opt.steps[hData.v.startIndex].trigger != false && hData.opt.steps[hData.v.startIndex].trigger != 'false'){
															hData.v.targetEl.trigger(hData.opt.steps[hData.v.startIndex].trigger);
														}
														
														/**/
														/*During callback function*/
														/**/
														if (targetOpt.during !== undefined) {targetOpt.during();}
													};
													
													/**/
													/*Animation scroll to selected item*/
													/**/
													if(targetOpt.contentPosition == 'center'){
														myFunc();
													}else{
														if(hData.v.targetEl.offset().top < $(window).scrollTop()){
															hData.v.scrolling = true;
															$('html,body').animate({scrollTop:(hData.v.targetEl.offset().top - $(window).height()/2)},changeDelay).promise().then(function(){
																setTimeout(function(){
																	hData.v.scrolling = false;
																	myFunc();	
																},1);
															});
														}else{
															if((hData.v.targetEl.offset().top + hData.v.targetEl.outerHeight()) > ($(window).scrollTop() + $(window).height())){
																hData.v.scrolling = true;
																$('html,body').animate({scrollTop:(hData.v.targetEl.offset().top - $(window).height()/2)},changeDelay).promise().then(function(){
																	setTimeout(function(){
																		hData.v.scrolling = false;
																		myFunc();	
																	},1);
																});
															}else{
																myFunc();		
															}	
														}
													}
												}else{
													searchElement('hidden');
												}
											}else{
												searchElement('absent');
											}
										},targetOpt.delayBefore);
									}else{
										hData.v.startIndex--;
										
										/**/
										/*Triggered when sequence is over*/
										/**/
										hWrap.itour('destroy');
										if (hData.opt.end !== undefined) {hData.opt.end();}	
										if (hData.opt.finish !== undefined) {hData.opt.finish();}
										
										/**/
										/*Save step numbers in localStorage*/
										/**/
										if (typeof(Storage) !== "undefined"){
											var itour_data = {
												tourId:false
											};
											localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
										}
									}
								}else{
									console.log('step data is missing');	
								}
							},changeDelay);
						});
					};
					
					
					var startStep = function(){
						var startMess = $('.startDialog').add('.startOverlay');
						startMess.removeClass('hShow');
						
						var delayTimeout = 100;
						if(hWrap.data().itour_go){
							delayTimeout = 1;	
						}
						
						setTimeout(function(){
							startMess.remove();

							/**/
							/*Show the tour map block by default, if there is a flag*/
							/**/
							if(hData.opt.tourMapVisible === true  || hData.opt.tourMapVisible === "true"){
								$('html').addClass('hNavOpen');
							}
							$('html').removeClass('hIntroShow');
							
							/**/
							/*Correcting start index*/
							/**/
							hData.v.startIndex = (parseFloat(hData.opt.startStep) - 1);

							/**/
							/*Start step function*/
							/**/
							hStep();
						
						},delayTimeout)
					};
					
					
					/**/
					/*Detect unfinished tour*/
					/**/
					var storageDetect = function(){
						
						var introMess = $('.introDialog');
						introMess.removeClass('hShow');
						
						var delayTimeout = 100;
						if(hWrap.data().itour_go){
							delayTimeout = 1;	
						}
						
						setTimeout(function(){
							introMess.remove();
								if (typeof(Storage) !== 'undefined'){
									if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
										var nowLoc = location.href;
										var itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
										if(itour_data.tourId == hData.v.tourid){
											if(hWrap.data().itour_go){
												hData.v.stepValue = itour_data.stepValue;
												hData.opt.startStep = hData.v.stepValue;	
												startStep();
											}else{
												if(hData.opt.tourContinue){
													hData.v.startDialog = $('<div>')
														.addClass('startDialog hContBlock')
														.html('<div class="hContHeader">'+hData.opt.lang.contDialogTitle+'</div><div class="hContBody">'+hData.opt.lang.contDialogContent+'</div><div class="hContFooter" style="text-align:right"><span class="hBtn startOverBtn">'+hData.opt.lang.contDialogBtnBegin+'</span><span class="hBtn continueBtn">'+hData.opt.lang.contDialogBtnContinue+'</span></div>')
														.appendTo('body');
														
													if(hData.opt.textDirection == "rtl"){
														hData.v.startDialog.addClass('rtl');
													}	
													
													hData.v.startOverlay = $('.startOverlay');
													if(!hData.v.startOverlay.length){
														hData.v.startOverlay = $('<div>').addClass('startOverlay').appendTo('body');
													}
													hData.v.stepValue = itour_data.stepValue;
			
													var pageLoc = itour_data.page;
													if(nowLoc.search(escapeRe(pageLoc)) === -1 && pageLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== pageLoc){
														hData.v.otherPage = itour_data.page; 
													}else{
														hData.v.otherPage = false; 	
													}
													setTimeout(function(){
														hData.v.startDialog.add(hData.v.startOverlay).addClass('hShow');
													},100);
												}else{
													startStep();	
												}
											}
											
										}else{
											
											if(parseFloat(hData.opt.startStep) < 1) targetLoc = 1;
											if(targetLoc > hData.opt.steps.length) targetLoc = hData.opt.steps.length;
											
											var targetLoc = hData.opt.steps[(parseFloat(hData.opt.startStep) - 1)].loc;
											if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
												
												var itour_go = {
													opt:toStringObj(hData.opt)
												};
												localStorage.setItem('itour_go',JSON.stringify(itour_go));
					
												/**/
												/*Save step numbers in localStorage*/
												/**/
												var itour_data = {};
												if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
													itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
												}
												$.extend(itour_data, {
													tourId:hData.v.tourid,
													stepValue:hData.opt.startStep
												});	
												localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
			
												/**/
												/*Redirect*/
												/**/
												location.href = targetLoc;
											}else{
												startStep();
											}	
										}
									}else{
										startStep();
									}
								}else{
									startStep();
								}
						},delayTimeout);
					};
					
					
					$(document).on('click','.hStartBtn',function(){
						storageDetect();
					});
					
					$(document).on('click','._hStartBtn',function(){

						/**/
						/*Set present and necessary location path*/
						/**/
						hData.v.startIndex = (parseFloat(hData.opt.startStep) - 1);
						var nowLoc = location.href;
						var targetLoc = nowLoc;
						var nextIndex = hData.v.startIndex;

						/**/
						/*Check presence of next step */
						/**/
						if(nextIndex < hData.opt.steps.length && nextIndex >= 0){
							targetLoc = hData.opt.steps[nextIndex].loc;
						}
						/**/
						/*compare the paths*/
						/**/					
						if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
							
							/*If the paths are not the same:*/
							
							/**/
							/*create new options object for transfer to another page*/
							/**/
							
							if (typeof(Storage) !== "undefined"){
								var itour_go = {
									opt:toStringObj(hData.opt)
								};
								localStorage.setItem('itour_go',JSON.stringify(itour_go));
								
								/**/
								/*Save step numbers in localStorage*/
								/*Get itour_data object from localStorage*/
								/**/
								var itour_data = {};
								if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
									itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
								}
								
								/**/
								/*Merge the getting object with the new values*/
								/**/
								$.extend(itour_data, {
									tourId:hData.v.tourid,
									stepValue:hData.opt.startStep
								});	
								
								/**/
								/*Save new data in localStorage*/
								/**/
								localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
	
								/**/
								/*Redirect*/
								/**/
								location.href = targetLoc;
							}else{
								brouserNotSupport()	
							}
						}else{
							storageDetect();
						}
					});
					
					$(document).on('click','.startOverBtn',function(){
						
						
						/**/
						/*Set present and necessary location path*/
						/**/
						var nowLoc = location.href;
						var targetLoc = nowLoc;
						var nextIndex = (parseFloat(hData.opt.startStep) - 1);

						/**/
						/*Check presence of next step */
						/**/
						if(nextIndex < hData.opt.steps.length && nextIndex >= 0){
							targetLoc = hData.opt.steps[nextIndex].loc;
						}
						/**/
						/*compare the paths*/
						/**/
						if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
							
							/*If the paths are not the same:*/
							
							/**/
							/*create new options object for transfer to another page*/
							/**/
							if (typeof(Storage) !== "undefined"){
								var itour_go = {
									opt:toStringObj(hData.opt)
								};
								
								localStorage.setItem('itour_go',JSON.stringify(itour_go));
							
								/**/
								/*Save step numbers in localStorage*/
								/*Get itour_data object from localStorage*/
								/**/
								var itour_data = {};
								if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
									itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
								}
								
								/**/
								/*Merge the getting object with the new values*/
								/**/
								$.extend(itour_data, {
									tourId:hData.v.tourid,
									stepValue:hData.opt.startStep
								});	
								
								/**/
								/*Save new data in localStorage*/
								/**/
								localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
	
								/**/
								/*Redirect*/
								/**/
								location.href = targetLoc;
							}else{
								brouserNotSupport()	
							}
						}else{
							startStep();	
						}
					});
					
					$(document).on('click','.continueBtn',function(){
						
						hData.opt.startStep = hData.v.stepValue;
						
						if(hData.v.otherPage){

							
							hData.v.customTarget = hData.v.otherPage;
							
							/**/
							/*Add Redirect Flag*/
							/**/
							if (typeof(Storage) !== 'undefined'){
								if(localStorage){
									var itour_go = {
										opt:toStringObj(hData.opt)
									}
									localStorage.setItem('itour_go',JSON.stringify(itour_go));
								}	
							}
							
							/**/
							/*Redirect*/
							/**/
							location.href = hData.v.customTarget;	
						}else{
							startStep();
						}
					});

					/**/
					/*Update position after window resize*/
					/**/
					$(window).on('resize scroll',function(){
						if(hData.ini){
							if(!hData.v.scrolling){
								hData.v.overlayPos(hData.v.targetEl);	
							}
							oddDetect();
						}
					});
					
					var brouserNotSupport = function(){
						console.log('Your browser does not support multi-page');
						hWrap.itour('destroy');
					};
					
					/**/
					/*This function call the next step*/
					/**/
					$(document).on('click','.hNext',function(){
						if(checkNextFunc() === true){
							/**/
							/*Set present and necessary location path*/
							/**/
							var nowLoc = location.href;
							var targetLoc = nowLoc;
							var nextIndex = (parseFloat(hData.v.startIndex) + 1);
	
							/**/
							/*Check presence of next step */
							/**/
							if(nextIndex < hData.opt.steps.length){
								targetLoc = hData.opt.steps[nextIndex].loc;
							}
							
							
							hData.v.targetEl.off(hData.opt.steps[hData.v.startIndex].event+'.itour');
							
							if(hData.v.customTargetSelector){
								$(hData.v.customTargetSelector).off(hData.v.customEvent+'.itour');
							}
							
							if(hData.opt.steps[hData.v.startIndex].event == 'next'){
								hData.opt.steps[hData.v.startIndex].ready = true;
								$('[data-hindex="'+hData.v.startIndex+'"]').addClass('hSuccess');
							}
							afterFunc();
							setTimeout(function(){
								/**/
								/*compare the paths*/
								/**/
								if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
									/*If the paths are not the same:*/
									/**/
									/*create new options object for transfer to another page*/
									/**/
									if (typeof(Storage) !== "undefined"){
										
										var itour_go = {
											opt:toStringObj(hData.opt)
										};
										
										localStorage.setItem('itour_go',JSON.stringify(itour_go));
		
										/**/
										/*Save step numbers in localStorage*/
										/*Get itour_data object from localStorage*/
										/**/
										var itour_data = {};
										if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
											itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
										}
										
										/**/
										/*Merge the getting object with the new values*/
										/**/
										$.extend(itour_data, {
											tourId:hData.v.tourid,
											stepValue:(parseFloat(itour_data.stepValue) + 1)
										});	
										
										/**/
										/*Save new data in localStorage*/
										/**/
										localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
			
										/**/
										/*Redirect*/
										/**/
										location.href = targetLoc;
									}else{
										brouserNotSupport()	
									}
								}else{								
									hData.v.startIndex++;
									hData.v.event = 'next';
									hStep();
								}
							},hData.opt.steps[hData.v.startIndex].delayAfter);
						}else{
							messageErrorNext();
						}
					});
					
					/**/
					/*This function call the prev step*/
					/**/
					$(document).on('click','.hPrev',function(){
						if(checkPrevFunc() === true){

							/**/
							/*Set present and necessary location path*/
							/**/
							var nowLoc = location.href;
							var targetLoc = nowLoc;
							var nextIndex = (parseFloat(hData.v.startIndex) - 1);
							
							/**/
							/*Check presence of next step */
							/**/
							if(nextIndex >= 0){
								targetLoc = hData.opt.steps[nextIndex].loc;
							}
							
							hData.v.targetEl.off(hData.opt.steps[hData.v.startIndex].event+'.itour');
							
							if(hData.v.customTargetSelector){
								$(hData.v.customTargetSelector).off(hData.v.customEvent+'.itour');
							}
							
							if(hData.opt.steps[hData.v.startIndex].event == 'prev'){
								hData.opt.steps[hData.v.startIndex].ready = true;
								$('[data-hindex="'+hData.v.startIndex+'"]').addClass('hSuccess');
							}
							afterFunc();
							setTimeout(function(){
							
								/**/
								/*compare the paths*/
								/**/
								if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
									/*If the paths are not the same:*/
									/**/
									/*create new options object for transfer to another page*/
									/**/
									if (typeof(Storage) !== "undefined"){
										var itour_go = {
											opt:toStringObj(hData.opt)
										};
										
										localStorage.setItem('itour_go',JSON.stringify(itour_go));
									
										/**/
										/*Save step numbers in localStorage*/
										/*Get itour_data object from localStorage*/
										/**/
										var itour_data = {};
										if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
											itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
										}
										
										/**/
										/*Merge the getting object with the new values*/
										/**/
										$.extend(itour_data, {
											tourId:hData.v.tourid,
											stepValue:(parseFloat(itour_data.stepValue) - 1)
										});	
										
										/**/
										/*Save new data in localStorage*/
										/**/
										localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));
			
										/**/
										/*Redirect*/
										/**/
										location.href = targetLoc;
									}else{
										brouserNotSupport()	
									}
								}else{
									hData.v.startIndex--;	
									hData.v.event = 'prev';
									hStep();
								}
							},hData.opt.steps[hData.v.startIndex].delayAfter);	
						}else{
							messageErrorPrev();	
						}
					});
					
					/**/
					/*This function off tour*/
					/**/
					$(document).on('click','.hClose',function(){
						hWrap.itour('destroy');
						if (hData.opt.end !== undefined) {hData.opt.end();}	
						if (hData.opt.abort !== undefined) {hData.opt.abort();}
					});
					
					/**/
					/*This function pass custom events*/
					/**/
					if(hData.opt.tourMapJump){
						$(document).on('click','[data-hindex]',function(){
							var stepLink = $(this);
							var stepLinkIndex = stepLink.attr('data-hindex');
							var nowLoc = location.href;
							var targetLoc = hData.opt.steps[stepLinkIndex].loc;
							
							
							
							if(parseFloat(hData.v.startIndex) !== parseFloat(stepLinkIndex)){
								
								if(nowLoc.search(escapeRe(targetLoc)) === -1 && targetLoc.search(escapeRe(nowLoc)) === -1 && nowLoc !== targetLoc){
									
									var itour_go = {
										opt:toStringObj(hData.opt)
									};
									localStorage.setItem('itour_go',JSON.stringify(itour_go));
		
									/**/
									/*Save step numbers in localStorage*/
									/**/
									var itour_data = {};
									if(localStorage.getItem('itour_data-'+hData.opt.tourID)){
										itour_data = $.parseJSON(localStorage.getItem('itour_data-'+hData.opt.tourID));
									}
									$.extend(itour_data, {
										tourId:hData.v.tourid,
										stepValue:(parseFloat(stepLinkIndex) + 1)
									});	
									localStorage.setItem('itour_data-'+hData.opt.tourID,JSON.stringify(itour_data));

									/**/
									/*Redirect*/
									/**/
									location.href = targetLoc;
								}else{
									
									hData.v.targetEl.off(hData.opt.steps[hData.v.startIndex].event+'.itour');
									
									if(hData.v.customTargetSelector){
										$(hData.v.customTargetSelector).off(hData.v.customEvent+'.itour');
									}
									afterFunc();
									setTimeout(function(){
										hData.v.startIndex = stepLinkIndex;	
										hStep();
									},hData.opt.steps[hData.v.startIndex].delayAfter);		
								}
							}
						});
					}else{
						$('html').addClass('hMapJump-disable');
					}
					
					/**/
					/*This function off tour*/
					/**/
					if(hData.opt.overlayClickable){
						$(document).on(clickEvent,'.hOverlay',function(e){
							if (touchMoving) return false;
							if(!$(e.target).is('.hContPos') && !$('.hContPos').find($(e.target)).length && hData.v.overlayEnable){
								hWrap.itour('destroy');
								if (hData.opt.end !== undefined) {hData.opt.end();}	
								if (hData.opt.abort !== undefined) {hData.opt.abort();}	
							}
						});
					}
					
					/**/
					/*Tour map toggle*/
					/**/
					hData.v.hNavBtn.on('click',function(){
						if($('html').is('.hNavOpen')){
							$('html').removeClass('hNavOpen');
							hData.opt.tourMapVisible = false;
						}else{
							$('html').addClass('hNavOpen');	
							hData.opt.tourMapVisible = true;	
						}
					});
					hData.v.hHide.on('click',function(){
						if($('html').is('.hNavOpen')){
							$('html').removeClass('hNavOpen');		
						}else{
							$('html').addClass('hNavOpen');		
						}
					});
					
					/**/
					/*If steps is more then 0*/
					/**/
					hData.ini = true;
					if(hData.opt.steps.length == 0){
						console.log('Mess: All target elements is absent.');
						console.log('Council: Check the "Steps" parameter.')
						hWrap.itour('destroy');
					}else{

						/**/
						/*Creating intro message*/
						/**/
						if (hData.opt.introShow){
							if(hWrap.data().itour_go){
								storageDetect();
							}else{							
								$('html').addClass('hIntroShow');
								hData.v.introDialog = $('<div>')
									.addClass('introDialog hContBlock')
									.html('<div class="hContImage"></div><div class="hContHeader">'+hData.opt.lang.introTitle+'</div><div class="hContBody">'+hData.opt.lang.introContent+'</div><div class="hContFooter" style="text-align:right"><span class="hBtn hAbortBtn hClose">'+hData.opt.lang.introDialogBtnCancel+'</span><span class="hBtn hStartBtn">'+hData.opt.lang.introDialogBtnStart+'</span></div>')
									.appendTo('body');
								if(hData.opt.introCover){
									$('<img>')
									.attr('src',hData.opt.introCover)
									.addClass('hTourCover')
									.on('load',function(){
										$(this).appendTo(hData.v.introDialog.find('.hContImage'));
										var coverHeight = $(this).height().toFixed();
										if(coverHeight & 1){
											coverHeight = (parseFloat(coverHeight)+1);
										}
										$(this).height(coverHeight);
									});
								}
								if(hData.opt.textDirection == "rtl"){
									hData.v.introDialog.addClass('rtl');
								}	
								hData.v.startOverlay = $('<div>').addClass('startOverlay').appendTo('body');
								setTimeout(function(){
									hData.v.introDialog.add(hData.v.startOverlay).addClass('hShow');
								},100);
							}
						}else{
							storageDetect();
						}
					}

					/**/
					/*adding create indicator*/
					/**/
					hWrap.addClass('hCreate');
					if (hData.opt.create !== undefined) {hData.opt.create();}
					

				}else{
					console.log('Warning! \nDetected re-initialization of plugin. Check the code calling the plugin!');
				}
			});
		},
		
		/**/
		/*Destroe Method*/
		/**/
		destroy: function (arg) {
			return this.each(function (e) {
				var hWrap = $('body');
				var hData = hWrap.data().itour;
				if(hData){
					if(hData.ini){
						
						localStorage.removeItem('itour_go');
						hWrap.data().itour_go = false;
						
						$('.itour-highlight').removeClass('itour-highlight');
						$('html').removeAttr('data-step');
						/**/
						/*Return style*/
						/**/
						hWrap.removeClass('hWrap hCreate');
						if(hData.v.startStyle){
							hWrap.attr({style:hData.v.startStyle});
						}else{
							hWrap.removeAttr('style');	
						}
						$('html').removeClass('hNavOpen');
						$('html').removeClass('hIntroShow');
						$('html').removeClass('hNav-disable');
						$('html').removeClass('hMapJump-disable');
						$('html').removeClass('vh-odd');
						$('html').removeClass('vw-odd');
						if(hData.opt.CSSClass){
							$('html').removeClass(hData.opt.CSSClass);	
						}	
						
						$('[data-uid]').removeAttr('data-uid');
						
						
						/**/
						/*Delete plugin elements*/
						/**/
						$(hData.v.hContPos).remove();
						$(hData.v.overlays).remove();
						$(hData.v.hOverlayDisable).remove();
						$(hData.v.hNavWrap).remove();
						$(hData.v.hNavPos).remove();
						$(hData.v.introDialog).remove();
						$(hData.v.startOverlay).remove();
						
						/**/
						/*Events off*/
						/**/
						$(document).off('click','.startOverBtn');
						$(document).off('click','.continueBtn');
						$(document).off('click','.hNext');
						$(document).off('click','.hPrev');
						$(document).off('click','.hStartBtn');
						if(hData.opt.steps[hData.v.startIndex]){
							if(hData.v.targetEl){
								hData.v.targetEl.off(hData.opt.steps[hData.v.startIndex].event+'.itour');
							}
						}
						
						if(hData.v.customTargetSelector){
							$(hData.v.customTargetSelector).off(hData.v.customEvent+'.itour');
						}
						
						$(document).off('click','.hClose');
						hData.v.hHide.off('click');
						$(document).off('click','.hOverlay');
						$(document).off('click','[data-hindex]');
						$(document).off('keyup.itour');
						hData.v.hNavBtn.off('click');
						
						/**/
						/*Delete data*/
						/**/
						hData.ini = false;
						if(!arg){
							delete hWrap.data().itour;
						}
					}else{
						console.log('Warning! \nDetected call of method Destroy for an element that is not associated with plugin. Check the code!');	
					}
				}
			});
		}
	};
	$.fn.itour = function (method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error("Method " + method + " in jQuery.itour doesn't exist");
		}
	};
	
	$(window).on('load',function(){
		
		if (typeof(Storage) !== 'undefined'){

			if(localStorage){

				if(localStorage.getItem('itour_go')){
					

					var typeDetect = function(per){
						
						
						
						if(typeof per == 'function'){
							//function
							return typeof per;
						}
						if(typeof per == 'boolean'){
							//boolean
							return typeof per;
						}
						if(typeof per == 'string'){
							//string
							return typeof per;
						}
						if(typeof per == 'number'){
							//number
							return typeof per;
						}
				
						if(typeof per == 'object'){
							if(Array.isArray(per)){
								//Array
								return 'array'
							}else{
								//Object
								return 'object'
							}
						}
					};
					var toFuncObjIndex = 0;
					var stringToFunc = function(obj){

						var newIndex = toFuncObjIndex++;
						var objEnter = obj;
						var tempObj = {};
						tempObj[newIndex] = {};

						for (var i in objEnter) {
							var key1 = i;
							var val = objEnter[key1];
							//array

							if(typeDetect(val) == 'array'){
								var valEach = val;
								for(var f = 0; f < valEach.length; f++){
									if(typeDetect(valEach[f]) == 'object'){
										var objMass = valEach[f];
										var val3 = stringToFunc(objMass);
										valEach[f] = val3;	
									}		
								}
								tempObj[newIndex][key1] = valEach;
							}
							//object
							if(typeDetect(val) == 'object'){
								var val2 = stringToFunc(val);	
								tempObj[newIndex][key1] = val2;
							}
							//string
							if(typeDetect(val) == 'string'){
								
								var words = 'function';
								var re = new RegExp('(^|\\s)' + words + '(?=\\s|$)', 'g');

								//function				
								//if(re.test(val)){
								if(val.search(/function\(/gi) != -1 || val.search(/function \(/gi) != -1){
									eval("tempObj[newIndex][key1] = "+objEnter[key1]+";");
								}else{
									tempObj[newIndex][key1] = val;
								}
							}
							//number, boolean
							if(typeDetect(val) == 'number' || typeDetect(val) == 'boolean'){
								tempObj[newIndex][key1] = val;	
							}
						}
						return tempObj[newIndex];
					};
					
					$('body').data().itour_go = true;
					var itour_go = $.parseJSON(localStorage.getItem('itour_go'));
					var opt = itour_go.opt;	
					var restoreObject = stringToFunc(opt);
					$('body').itour(restoreObject);
				}
			}					
		}
	});
	
})(jQuery);