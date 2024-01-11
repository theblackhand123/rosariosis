!function(t,e){var a="2.7.0",r=t.CombineConfig("TeX.noErrors",{disabled:!1,multiLine:!0,inlineDelimiters:["",""],style:{"font-size":"90%","text-align":"left",color:"black",padding:"1px 3px",border:"1px solid"}}),i=" ";MathJax.Extension["TeX/noErrors"]={version:a,config:r},t.Register.StartupHook("TeX Jax Ready",function(){var e=MathJax.InputJax.TeX.formatError;MathJax.InputJax.TeX.Augment({formatError:function(a,o,n,s){if(r.disabled)return e.apply(this,arguments);var l=a.message.replace(/\n.*/,"");t.signal.Post(["TeX Jax - parse error",l,o,n,s]);var m=r.inlineDelimiters,p=n||r.multiLine;return n||(o=m[0]+o+m[1]),o=p?o.replace(/ /g,i):o.replace(/\n/g," "),MathJax.ElementJax.mml.merror(o).With({isError:!0,multiLine:p})}})}),t.Register.StartupHook("HTML-CSS Jax Config",function(){t.Config({"HTML-CSS":{styles:{".MathJax .noError":t.Insert({"vertical-align":t.Browser.isMSIE&&r.multiLine?"-2px":""},r.style)}}})}),t.Register.StartupHook("HTML-CSS Jax Ready",function(){var t=MathJax.ElementJax.mml,e=MathJax.OutputJax["HTML-CSS"],a=t.math.prototype.toHTML,r=t.merror.prototype.toHTML;t.math.Augment({toHTML:function(t,e){var r=this.data[0];return r&&r.data[0]&&r.data[0].isError?(t.style.fontSize="",t=this.HTMLcreateSpan(t),t.bbox=r.data[0].toHTML(t).bbox):t=a.apply(this,arguments),t}}),t.merror.Augment({toHTML:function(t){if(!this.isError)return r.apply(this,arguments);t=this.HTMLcreateSpan(t),t.className="noError",this.multiLine&&(t.style.display="inline-block");for(var a=this.data[0].data[0].data.join("").split(/\n/),i=0,o=a.length;i<o;i++)e.addText(t,a[i]),i!==o-1&&e.addElement(t,"br",{isMathJax:!0});var n=e.getHD(t.parentNode),s=e.getW(t.parentNode);if(o>1){var l=(n.h+n.d)/2,m=e.TeX.x_height/2;t.parentNode.style.verticalAlign=e.Em(n.d+(m-l)),n.h=m+l,n.d=l-m}return t.bbox={h:n.h,d:n.d,w:s,lw:0,rw:s},t}})}),t.Register.StartupHook("SVG Jax Config",function(){t.Config({SVG:{styles:{".MathJax_SVG .noError":t.Insert({"vertical-align":t.Browser.isMSIE&&r.multiLine?"-2px":""},r.style)}}})}),t.Register.StartupHook("SVG Jax Ready",function(){var t=MathJax.ElementJax.mml,a=t.math.prototype.toSVG,r=t.merror.prototype.toSVG;t.math.Augment({toSVG:function(t,e){var r=this.data[0];return t=r&&r.data[0]&&r.data[0].isError?r.data[0].toSVG(t):a.apply(this,arguments)}}),t.merror.Augment({toSVG:function(t){if(!this.isError||"math"!==this.Parent().type)return r.apply(this,arguments);t=e.addElement(t,"span",{className:"noError",isMathJax:!0}),this.multiLine&&(t.style.display="inline-block");for(var a=this.data[0].data[0].data.join("").split(/\n/),i=0,o=a.length;i<o;i++)e.addText(t,a[i]),i!==o-1&&e.addElement(t,"br",{isMathJax:!0});if(o>1){var n=t.offsetHeight/2;t.style.verticalAlign=-n+n/o+"px"}return t}})}),t.Register.StartupHook("NativeMML Jax Ready",function(){var t=MathJax.ElementJax.mml,e=MathJax.Extension["TeX/noErrors"].config,a=t.math.prototype.toNativeMML,r=t.merror.prototype.toNativeMML;t.math.Augment({toNativeMML:function(t){var e=this.data[0];return t=e&&e.data[0]&&e.data[0].isError?e.data[0].toNativeMML(t):a.apply(this,arguments)}}),t.merror.Augment({toNativeMML:function(t){if(!this.isError)return r.apply(this,arguments);t=t.appendChild(document.createElement("span"));for(var a=this.data[0].data[0].data.join("").split(/\n/),i=0,o=a.length;i<o;i++)t.appendChild(document.createTextNode(a[i])),i!==o-1&&t.appendChild(document.createElement("br"));this.multiLine&&(t.style.display="inline-block",o>1&&(t.style.verticalAlign="middle"));for(var n in e.style)if(e.style.hasOwnProperty(n)){var s=n.replace(/-./g,function(t){return t.charAt(1).toUpperCase()});t.style[s]=e.style[n]}return t}})}),t.Register.StartupHook("PreviewHTML Jax Config",function(){t.Config({PreviewHTML:{styles:{".MathJax_PHTML .noError":t.Insert({"vertical-align":t.Browser.isMSIE&&r.multiLine?"-2px":""},r.style)}}})}),t.Register.StartupHook("PreviewHTML Jax Ready",function(){var t=MathJax.ElementJax.mml,e=MathJax.HTML,a=t.merror.prototype.toPreviewHTML;t.merror.Augment({toPreviewHTML:function(t){if(!this.isError)return a.apply(this,arguments);t=this.PHTMLcreateSpan(t),t.className="noError",this.multiLine&&(t.style.display="inline-block");for(var r=this.data[0].data[0].data.join("").split(/\n/),i=0,o=r.length;i<o;i++)e.addText(t,r[i]),i!==o-1&&e.addElement(t,"br",{isMathJax:!0});return t}})}),t.Register.StartupHook("CommonHTML Jax Config",function(){t.Config({CommonHTML:{styles:{".mjx-chtml .mjx-noError":t.Insert({"line-height":1.2,"vertical-align":t.Browser.isMSIE&&r.multiLine?"-2px":""},r.style)}}})}),t.Register.StartupHook("CommonHTML Jax Ready",function(){var t=MathJax.ElementJax.mml,e=MathJax.OutputJax.CommonHTML,a=MathJax.HTML,r=t.merror.prototype.toCommonHTML;t.merror.Augment({toCommonHTML:function(t){if(!this.isError)return r.apply(this,arguments);t=e.addElement(t,"mjx-noError");for(var i=this.data[0].data[0].data.join("").split(/\n/),o=0,n=i.length;o<n;o++)a.addText(t,i[o]),o!==n-1&&e.addElement(t,"br",{isMathJax:!0});var s=this.CHTML=e.BBOX.zero();if(s.w=t.offsetWidth/e.em,n>1){var l=1.2*n/2;s.h=l+.25,s.d=l-.25,t.style.verticalAlign=e.Em(.45-l)}else s.h=1,s.d=.2+2/e.em;return t}})}),t.Startup.signal.Post("TeX noErrors Ready")}(MathJax.Hub,MathJax.HTML),MathJax.Ajax.loadComplete("[MathJax]/extensions/TeX/noErrors.js");