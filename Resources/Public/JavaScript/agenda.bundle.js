!function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=1)}([function(e,t){e.exports=jQuery},function(e,t,n){"use strict";var o=n(0);o(document).ready((function(){window.doEqualizeAgenda=()=>{o(".oa-list").each((function(){var e=o(this).find(".oa-list__item");if(0==e.length)return!1;var t=[],n=Math.floor(o(this).width()/e.first().width());e.each((function(e,t){o(t).height("auto")})),e.each((function(e,r){null==t[Math.floor(e/n)]&&(t[Math.floor(e/n)]=0),o(r).height()>t[Math.floor(e/n)]&&(t[Math.floor(e/n)]=o(r).height())})),e.each((function(e,r){o(r).height("auto"),o(r).height(t[Math.floor(e/n)])}))}))};let e=document.getElementsByClassName("field--type-openagenda")?document.getElementsByClassName("field--type-openagenda"):document.getElementsByClassName("oa-agenda oa-agenda--preview");if(e.length){new MutationObserver(e=>{window.doEqualizeAgenda()}).observe(e[0],{childList:!0,subtree:!0})}setTimeout(window.doEqualizeAgenda,250)}))}]);