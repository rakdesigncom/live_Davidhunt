define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function (_, uiRegistry, select) {
    'use strict';
    var menuId = ''; 
    return select.extend({
        initialize: function () {
            this._super();
            // component initialization logic
            menuId = this.value();
            this._onloadCustom(this.value());
            return this;
        },
        
        _onloadCustom: function (value) {
            if (value != undefined){
                menuId = value;
            }
            //console.log('Selected Menu ID: ' + menuId);

            var megamenu_type_numofcolumns = uiRegistry.get('index = megamenu_type_numofcolumns');
            var megamenu_type_subcol = uiRegistry.get('index = megamenu_type_subcol');
            var megamenu_type_half_pos = uiRegistry.get('index = megamenu_type_half_pos');
            var megamenu_type_leftblock = uiRegistry.get('index = megamenu_type_leftblock');
            var megamenu_type_leftblock_w = uiRegistry.get('index = megamenu_type_leftblock_w');
            var megamenu_type_rightblock = uiRegistry.get('index = megamenu_type_rightblock');
            var megamenu_type_rightblock_w = uiRegistry.get('index = megamenu_type_rightblock_w');
            var megamenu_type_header = uiRegistry.get('index = megamenu_type_header');
            var megamenu_type_footer = uiRegistry.get('index = megamenu_type_footer');
            var megamenu_type_viewmore = uiRegistry.get('index = megamenu_type_viewmore');
            var megamenu_type_subcatlevel = uiRegistry.get('index = megamenu_type_subcatlevel');
            var megamenu_type_showtitle = uiRegistry.get('index = megamenu_type_showtitle');
            
            var megamenu_type_imgpos = uiRegistry.get('index = megamenu_type_imgpos');
            var megamenu_show_catimage = uiRegistry.get('index = megamenu_show_catimage');
            var megamenu_show_catimage_img = uiRegistry.get('index = megamenu_show_catimage_img');
            var megamenu_show_catimage_width = uiRegistry.get('index = megamenu_show_catimage_width');
            var megamenu_show_catimage_height = uiRegistry.get('index = megamenu_show_catimage_height');
            
            //var megamenu_type_labeltx = uiRegistry.get('index = megamenu_type_labeltx');
            //var megamenu_type_labelclr = uiRegistry.get('index = megamenu_type_labelclr');
            var megamenu_type_class = uiRegistry.get('index = megamenu_type_class');
            
            var logoutTimer = '';
            if (megamenu_type_numofcolumns == undefined ||
                megamenu_type_subcol == undefined ||
                megamenu_type_half_pos == undefined ||
                megamenu_type_leftblock == undefined ||
                megamenu_type_leftblock_w == undefined ||
                megamenu_type_rightblock == undefined ||
                megamenu_type_rightblock_w == undefined ||
                megamenu_type_header == undefined ||
                megamenu_type_footer == undefined ||
                megamenu_type_class == undefined ||
                megamenu_type_viewmore == undefined ||
                megamenu_type_showtitle == undefined ||
                megamenu_type_imgpos == undefined ||
                megamenu_type_imgpos == undefined ||
                megamenu_show_catimage == undefined ||
                megamenu_show_catimage_img == undefined ||
                megamenu_show_catimage_width == undefined ||
                megamenu_show_catimage_height == undefined ||
                megamenu_type_subcatlevel == undefined
               ) {
                logoutTimer = setTimeout(this._onloadCustom, 2000);
                /*
                setTimeout(function() {
                    this.checkOpt;
                }, 5000);
                */
                
            } else {
                clearTimeout(logoutTimer);
                megamenu_type_numofcolumns.show();
                megamenu_type_half_pos.show();
                megamenu_type_leftblock.show();
                megamenu_type_leftblock_w.show();
                megamenu_type_rightblock.show();
                megamenu_type_rightblock_w.show();
                megamenu_type_header.show();
                megamenu_type_footer.show();
                
                megamenu_type_subcol.hide();
                megamenu_type_showtitle.hide();
                megamenu_type_viewmore.hide();
                megamenu_type_subcatlevel.hide();
                megamenu_type_imgpos.hide();
                
                megamenu_show_catimage.show();
                megamenu_show_catimage_img.show();
                megamenu_show_catimage_width.show();
                megamenu_show_catimage_height.show();

                //megamenu_type_labeltx.show();
                //megamenu_type_labelclr.show();
                megamenu_type_class.show();

                if (menuId == 0) {
                    /*
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                    */
                    megamenu_type_class.hide();
                } else if (menuId == 1) {
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 2) {
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 3) {
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 4) {
                    
                } else if (menuId == 5) {
                    megamenu_type_viewmore.show();
                    megamenu_type_subcol.show();
                } else if (menuId == 6) {
                    megamenu_type_half_pos.hide();
                } else if (menuId == 7) {
                    megamenu_type_half_pos.hide();
                    megamenu_type_viewmore.show();
                    megamenu_type_subcol.show();
                } else if (menuId == 8) {
                    megamenu_type_viewmore.show();
                    megamenu_type_half_pos.hide();
                } else if (menuId == 9) {
                    megamenu_type_half_pos.hide();
                } else if (menuId == 10) {
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 14) {
                    //megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 11) {
                    megamenu_type_viewmore.show();
                    megamenu_type_subcatlevel.show();
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                    megamenu_type_leftblock.hide();
                    megamenu_type_leftblock_w.hide();
                    megamenu_type_rightblock.hide();
                    megamenu_type_rightblock_w.hide();
                    megamenu_type_header.hide();
                    megamenu_type_footer.hide();
                } else if (menuId == 12 || menuId == 13) {
                    megamenu_type_numofcolumns.hide();
                    megamenu_type_half_pos.hide();
                } else if (menuId == 15 || menuId == 16) {
                    megamenu_type_showtitle.show();
                    megamenu_type_imgpos.show();
                    megamenu_type_half_pos.hide();
                } else {
                    
                }
                //return this._super();
            }
        },
        
        onUpdate: function (value) {
            this._onloadCustom(value);
            return this._super();
        }
    });
});
