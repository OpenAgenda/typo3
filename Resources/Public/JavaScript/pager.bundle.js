!function(e){var n={};function t(a){if(n[a])return n[a].exports;var r=n[a]={i:a,l:!1,exports:{}};return e[a].call(r.exports,r,r.exports,t),r.l=!0,r.exports}t.m=e,t.c=n,t.d=function(e,n,a){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:a})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var a=Object.create(null);if(t.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var r in e)t.d(a,r,function(n){return e[n]}.bind(null,r));return a},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="",t(t.s=5)}({0:function(e,n){e.exports=jQuery},5:function(e,n,t){"use strict";var a=t(0);a(document).ready((function(){a("body").on("click","#oa-wrapper .pager__link",e=>{e.preventDefault(),e.stopPropagation();const n=a(e.target);let t=n.attr("href");void 0===t&&(t=n.parent().attr("href")),void 0===t&&(t=n.parent().parent().attr("href")),a("#loading").show(),a.ajax({url:settingsOpenagendaAjaxUrl+"&"+t+"&settingsOpenagendaCalendarUid="+settingsOpenagendaCalendarUid+"&settingsOpenagendaPage="+settingsOpenagendaPage+"&settingsOpenagendaLanguageId="+settingsOpenagendaLanguageId+"&settingsOpenagendaLanguage="+settingsOpenagendaLanguage+"&settingsOpenagendaColumns="+settingsOpenagendaColumns+"&settingsOpenagendaEventsPerPage="+settingsOpenagendaEventsPerPage+"&settingsOpenagendaPreFilter="+settingsOpenagendaPreFilter}).done(e=>{a("#oa-wrapper").html(e.content),a("#loading").hide(),document.getElementById("oa-wrapper").scrollIntoView()})})}))}});