// Mod: Date Tips for Xin Calendar 2 (In-Page)
// Copyright 2004  Xin Yang    All Rights Reserved.

function addDateTips(co,cy,db,da,cz,ec,date,jt){xc_eh(co,"en",xc_fw(date,ec||xcDateFormat,xc_da),[cy,db,xc_bm],0);xc_bn[xc_bm++]=[cz||xcCSSDateTipBox,jt,da||xcCSSDateTipBoxTitle,date]};var xc_bm=0,xc_bl=null,xc_bo=0,xc_bn=new Array();function xc_ec(cc,jw){var td=cc.parentElement?cc.parentElement:cc;clearTimeout(xc_bo);xc_bo=setTimeout("xc_fb("+xc_cm(td)+","+xc_cn(td)+","+td.offsetWidth+","+td.offsetHeight+","+jw+")",xcDateTipTiming)};function xc_ea(){clearTimeout(xc_bo);xc_bo=setTimeout("xc_cx()",xcDateTipTiming)};function xc_eb(){clearTimeout(xc_bo)};function xc_dz(){xc_bo=setTimeout("xc_cx()",xcDateTipTiming)};function xc_dd(jw){if(xc_bl==null){xc_bl=xc_db();xc_bl.className=xc_bn[jw][0];if(xcIsIE55up&&!xcIsMac){xc_bl.onmouseenter=xc_eb;xc_bl.onmouseleave=xc_dz}else{xc_bl.onmouseover=xc_eb;xc_bl.onmouseout=xc_dz};xc_bl.jv=-1};if(xc_bl.jv!=jw){xc_bl.scrollTop=0;xc_bl.scrollLeft=0;xc_bl.innerHTML=(xcDateTipBoxTitleSwitch?xcDIV(xc_bn[jw][2],xc_bn[jw][3]):"")+xc_bn[jw][1]}};function xc_fb(x,y,w,h,jw){xc_dd(jw);if(xc_bl.jv!=jw){xc_cx();var jx=xc_bl.offsetWidth,ju=xc_bl.offsetHeight;var dx=dy=0;if(xcDateTipBoxPosition==0||xcDateTipBoxPosition==6){dx=xcDateTipBoxAlign==0?0:xcDateTipBoxAlign==1?Math.floor((w-jx)/2):(w-jx);dy=xcDateTipBoxPosition==0?-ju:h}else{dx=xcDateTipBoxPosition==9?-jx:w;dy=xcDateTipBoxValign==0?0:xcDateTipBoxValign==1?Math.floor((h-ju)/2):(h-ju)};dx+=xcDateTipBoxOffsetX;dy+=xcDateTipBoxOffsetY;if(xc_bl.gy){xc_bl.gy.style.width=xc_bl.offsetWidth+"px";xc_bl.gy.style.height=xc_bl.offsetHeight+"px";xc_bl.gy.style.zIndex=xc_bl.style.zIndex-1;xc_dp(xc_bl.gy,x+dx,y+dy)};xc_bl.style.zIndex=++xcBaseZIndex;xc_dp(xc_bl,x+dx,y+dy);if(xc_bl.gy){xc_ez(xc_bl.gy)};if(xcIsIE&&!xcIsMac&&typeof(xc_bl.filters)!="undefined"&&typeof(xc_bl.filters)!="unknown"&&xc_bl.filters.length>0){for(var i=0;i<xc_bl.filters.length;i++){try{xc_bl.filters[i].Apply()}catch(fl){}}};xc_ez(xc_bl);if(xcIsIE&&!xcIsMac&&typeof(xc_bl.filters)!="undefined"&&typeof(xc_bl.filters)!="unknown"&&xc_bl.filters.length>0){for(var i=0;i<xc_bl.filters.length;i++){try{xc_bl.filters[i].Play()}catch(fl){}}};xc_bl.jv=jw}};function xc_cx(){if(xc_bl){if(xc_bl.gy){xc_dp(xc_bl.gy,-1000,-1000)};xc_dp(xc_bl,-1000,-1000);if(xc_bl.gy){xc_cw(xc_bl.gy)};xc_cw(xc_bl);xc_bl.jv=-1}};function xc_dk(){var gm=xc_bp(this);return gm?("xc_ec(this,"+gm[2]+");"):""};function xc_dj(){var gm=xc_bp(this);return gm?("xc_ea();"):""};function xc_ah(date){clearTimeout(xc_bo);xc_cx();afterSetDateValue(this.ir,this.jk,date,this.gx)};function xc_bp(bj){return bj.ge("en",bj.date)};xc_fd[xc_fd.length]=xc_bp;