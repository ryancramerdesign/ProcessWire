/**
 * PreElementFix plugin
 * Version: 0.4
 * Author: tan@enonic.com
 * License: LGPL
 */
(function() {

    tinymce.PluginManager.requireLangPack('preelementfix');

    tinymce.create('tinymce.plugins.PreElementFix', {
        init : function( ed, url )
        {
            var t = this;

            ed.onInit.add(function( ed )
            {
                ed.dom.loadCSS(url + '/css/preelementfix.css');

                var cssAliasesSettings = ed.settings.preelementfix_css_aliases;

                if ( cssAliasesSettings )
                {
                    tinymce.dom.Event.add(ed.getBody(), 'mouseover', function(e)
                    {
                        var target = e.target;

                        if ( target.nodeName === 'PRE' )
                        {
                            var cssAlias = t._getCssAlias(ed, target.className);

                            if ( cssAlias )
                            {
                                t._createTooltip(ed, target, cssAlias);
                            }
                        }
                    });
                }

                // Add context menu items.
                if ( ed && ed.plugins.contextmenu && cssAliasesSettings )
                {
                    ed.plugins.contextmenu.onContextMenu.add(function( th, m, e )
                    {
                        if ( ed.dom.getParent(e, 'pre') )
                        {
                            var title, cls, disableMenuItem;
                            var preElement =  ed.dom.getParent(e, 'pre');

                            m.removeAll();

                            for ( var key in cssAliasesSettings )
                            {
                                title = key;
                                cls = cssAliasesSettings[key];

                                disableMenuItem = cls === preElement.className;

                                m.add({
                                    title: title,
                                    active: true,
                                    _cls: cls,
                                    onclick: function()
                                    {
                                        t._addCssAliasToPreElement(ed, this._cls);
                                    }
                                }).setDisabled(disableMenuItem);

                            }

                            m.addSeparator();

                            m.add({
                                title: 'preelementfix.mei_remove_css_alias',
                                onclick: function()
                                {
                                    t._removeCssAliasFromPreElement(ed);
                                }
                            });

                            // Hack to remove the insert table menu item.
                            if ( ed.plugins.table )
                            {
                                var lastMenuItem;

                                setTimeout(function() {
                                    for ( var mi in m.items )
                                    {
                                        lastMenuItem = mi;
                                    }

                                    lastMenuItem = m.items[lastMenuItem];

                                    if ( lastMenuItem.settings.cmd === 'mceInsertTable' )
                                    {
                                        lastMenuItem.remove();
                                    }

                                }, 10);
                            }
                        }
                    });
                }
            });

            // MSIE and WebKit inserts a new PRE element each time the user hits enter.
            // Gecko and Opera inserts a BR element. This will make sure that IE and WebKit has the same behaviour as Fx and Opera.
            if ( tinymce.isIE || tinymce.isWebKit )
            {
                ed.onKeyDown.add(function( ed, e )
                {
                    var brElement;
                    var selection = ed.selection;

                    if ( e.keyCode == 13 && selection.getNode().nodeName === 'PRE' )
                    {
                        selection.setContent('<br id="__preElementFix" /> ', {format : 'raw'}); // Do not remove the space after the BR element.

                        brElement = ed.dom.get('__preElementFix');
                        brElement.removeAttribute('id');
                        selection.select(brElement);
                        selection.collapse();
                        return tinymce.dom.Event.cancel(e);
                    }
                });
            }

            // Inserts a tab in Gecko and Opera when the user hits the tab key.
            if ( tinymce.isGecko || tinymce.isOpera )
            {
                ed.onKeyDown.add(function( ed, e )
                {
                    var selection = ed.selection;

                    if ( e.keyCode == 9 && selection.getNode().nodeName === 'PRE' )
                    {
                        selection.setContent('\t', {format : 'raw'});
                        return tinymce.dom.Event.cancel(e);
                    }
                });
            }

            if ( tinymce.isGecko )
            {
                ed.onSetContent.add(function( ed, o )
                {
                    t._replaceNewlinesWithBrElements(ed);
                });
            }

            ed.onPreProcess.add(function( ed, o )
            {
                t._replaceBrElementsWithNewlines(ed, o.node);

                if ( tinymce.isWebKit )
                {
                    t._removeSpanElementsInPreElementsForWebKit(ed, o.node);
                }

                var el = ed.dom.get('__preElementFixTooltip');
                ed.dom.remove(el);
            });
        },
        // -----------------------------------------------------------------------------------------------------------------

        _addCssAliasToPreElement: function( ed, cls )
        {
            var t = this;
            var dom = ed.dom;
            var selection = ed.selection;
            var selectedNode = selection.getNode();

            t._removeCssAliasFromPreElement(ed);

            dom.addClass(selectedNode, cls);

            ed.nodeChanged();
        },
        // -----------------------------------------------------------------------------------------------------------------

        _removeCssAliasFromPreElement: function( ed )
        {
            var dom = ed.dom;
            var selection = ed.selection;
            var selectedNode = selection.getNode();
            var cssAliases = ed.settings.preelementfix_css_aliases;

            if ( selectedNode.nodeName == 'PRE' )
            {
                for ( var key in cssAliases )
                {
                    dom.removeClass(ed.selection.getNode(), cssAliases[key]);
                }

                ed.nodeChanged();
            }
        },
        // -----------------------------------------------------------------------------------------------------------------

        _getCssAlias: function( ed, cssClass )
        {
            var cssAliases = ed.settings.preelementfix_css_aliases;

            for ( var key in cssAliases )
            {
                if ( cssAliases[key] === cssClass )
                {
                    return { text: key, cssCls: cssAliases[key] };
                }
            }

            return null;
        },
        // -----------------------------------------------------------------------------------------------------------------

        _createTooltip: function( ed, preElement, cssAlias )
        {
            var t = this;
            var dom = ed.dom;

            var aliasName = cssAlias.text;
            var aliasCssClass = cssAlias.cssCls;

            var tooltipElement = dom.create('div', {
                'id': '__preElementFixTooltip',
                'class': 'ui-pre-element-fix-tooltip ' + aliasCssClass
            }, '');

            dom.setStyles(tooltipElement, {
                'position': 'absolute',
                'top': '-3000px',
                'left': '-3000px'
            });

            dom.add(ed.getBody(), tooltipElement);
            dom.setHTML(tooltipElement, aliasName);

            tinymce.dom.Event.remove(preElement, 'mouseout', function() { t._removeTooltip(ed); } );
            tinymce.dom.Event.add(preElement, 'mouseout', function() { t._removeTooltip(ed); });

            var tooltipRect = dom.getRect(tooltipElement);
            var tooltipW = tooltipRect.w;

            var preElementRect = dom.getRect(preElement);
            var preElementX = preElementRect.x;
            var preElementY = preElementRect.y;
            var preElementW = preElementRect.w;

            dom.setStyles(tooltipElement, {
                top: preElementY + 'px',
                left: (preElementX + preElementW - tooltipW) + 'px'
            });
        },
        // -----------------------------------------------------------------------------------------------------------------

        _removeTooltip: function( ed )
        {
            var dom = ed.dom;
            var el = dom.get('__preElementFixTooltip');
            dom.remove(el);

        },
        // -----------------------------------------------------------------------------------------------------------------

        _replaceNewlinesWithBrElements: function( ed )
        {
            var t = this;
            var preElements = ed.dom.select('pre');
            for ( var i = 0; i < preElements.length; i++ )
            {
                preElements[i].innerHTML = t._nl2br(preElements[i].innerHTML);
            }
        },
        // -----------------------------------------------------------------------------------------------------------------

        _nl2br: function( text )
        {
            text = escape(text);
            var newlineChar;

            if(text.indexOf('%0D%0A') > -1 )
            {
                newlineChar = /%0D%0A/g ;
            }
            else if ( text.indexOf('%0A') > -1 )
            {
                newlineChar = /%0A/g ;
            }
            else if ( text.indexOf('%0D') > -1 )
                {
                    newlineChar = /%0D/g ;
                }

            if ( typeof(newlineChar) == "undefined")
            {
                return unescape( text );
            }
            else
            {
                return unescape( text.replace(newlineChar, '<br/>') );
            }
        },

        // -----------------------------------------------------------------------------------------------------------------

        _replaceBrElementsWithNewlines: function( ed, node )
        {
            var brElements = ed.dom.select('pre br', node);
            var newlineChar = tinymce.isIE ? '\r' : '\n';
            var newline;

            for ( var i = 0; i < brElements.length; i++ )
            {
                newline = ed.getDoc().createTextNode(newlineChar);

                ed.dom.insertAfter(newline, brElements[i]);
                ed.dom.remove(brElements[i]);
            }
        },
        // -----------------------------------------------------------------------------------------------------------------

        _removeSpanElementsInPreElementsForWebKit: function( ed, node )
        {
            // WebKit inserts a span element each time the users hits the tab key.
            // This removes the element.
            var spanElements = ed.dom.select('pre span', node);
            var space;
            for ( var i = 0; i < spanElements.length; i++ )
            {
                space = ed.getDoc().createTextNode(spanElements[i].innerHTML);
                ed.dom.insertAfter(space, spanElements[i]);
                ed.dom.remove(spanElements[i]);
            }
        },
        // -----------------------------------------------------------------------------------------------------------------

        getInfo : function() {
            return {
                longname : 'Pre Element Fix',
                author : 'tan@enonic.com',
                authorurl : 'http://www.enonic.com',
                infourl : 'http://www.enonic.com',
                version : "0.4b2"
            };
        }
    });

    tinymce.PluginManager.add('preelementfix', tinymce.plugins.PreElementFix);
})();