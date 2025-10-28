<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Sat, 10 Dec 2011 06:46:54 GMT
 */
if (! defined('NV_MAINFILE')) {
    die('Stop!!!');
}

if (! nv_function_exists('nv_block_news_view_cat')) {
    
    if (! nv_function_exists('nv_resize_crop_images')) {

        function nv_resize_crop_images($img_path, $width, $height, $module_name = '', $id = 0)
        {
            $new_img_path = str_replace(NV_ROOTDIR, '', $img_path);
            if (file_exists($img_path)) {
                $imginfo = nv_is_image($img_path);
                $basename = basename($img_path);
                $basename = preg_replace('/^\W+|\W+$/', '', $basename);
                $basename = preg_replace('/[ ]+/', '_', $basename);
                $basename = strtolower(preg_replace('/\W-/', '', $basename));
                if ($imginfo['width'] > $width or $imginfo['height'] > $height) {
                    $basename = preg_replace('/(.*)(\.[a-zA-Z]+)$/', $module_name . '_' . $id . '_\1_' . $width . '-' . $height . '\2', $basename);
                    if (file_exists(NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . $basename)) {
                        $new_img_path = NV_BASE_SITEURL . NV_TEMP_DIR . '/' . $basename;
                    } else {
                        $img_path = new NukeViet\Files\Image($img_path, NV_MAX_WIDTH, NV_MAX_HEIGHT);
                        
                        $thumb_width = $width;
                        $thumb_height = $height;
                        $maxwh = max($thumb_width, $thumb_height);
                        if ($img_path->fileinfo['width'] > $img_path->fileinfo['height']) {
                            $width = 0;
                            $height = $maxwh;
                        } else {
                            $width = $maxwh;
                            $height = 0;
                        }
                        
                        $img_path->resizeXY($width, $height);
                        $img_path->cropFromCenter($thumb_width, $thumb_height);
                        $img_path->save(NV_ROOTDIR . '/' . NV_TEMP_DIR, $basename);
                        if (file_exists(NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . $basename)) {
                            $new_img_path = NV_BASE_SITEURL . NV_TEMP_DIR . '/' . $basename;
                        }
                    }
                }
            }
            return $new_img_path;
        }
    }

    function nv_block_config_news_view_cat($module, $data_block, $lang_block)
    {
        global $nv_Cache, $site_mods;
        
        $array_style = array(
            'mainleft' => $lang_block['style_1'],
            'maintop' => $lang_block['style_0']
        );
        
        $html = '<tr>';
        $html .= '<td>' . $lang_block['catid'] . '</td>';
        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $site_mods[$module]['module_data'] . '_cat ORDER BY sort ASC';
        $list = $nv_Cache->db($sql, '', $module);
        $html .= '<td>';
        foreach ($list as $l) {
            $xtitle_i = '';
            
            if ($l['lev'] > 0) {
                for ($i = 1; $i <= $l['lev']; ++ $i) {
                    $xtitle_i .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                }
            }
            $html .= $xtitle_i . '<label><input type="checkbox" name="config_catid[]" value="' . $l['catid'] . '" ' . ((is_array($data_block['catid']) and in_array($l['catid'], $data_block['catid'])) ? ' checked="checked"' : '') . '</input>' . $l['title'] . '</label><br />';
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>' . $lang_block['numrow'] . '</td>';
        $html .= '<td><input type="text" class="form-control w200" name="config_numrow" size="5" value="' . $data_block['numrow'] . '"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>' . $lang_block['style'] . '</td>';
        $html .= '<td><select class="form-control w200" name="config_style">';
        foreach ($array_style as $index => $value) {
            $sl = $index == $data_block['style'] ? 'selected="selected"' : '';
            $html .= '<option value="' . $index . '" ' . $sl . '>' . $value . '</option>';
        }
        $html .= '</select></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>' . $lang_block['imagesize'] . '</td>';
        $html .= '<td><input type="size" class="form-control w100 pull-left" name="config_imagesize_w" size="5" value="' . $data_block['imagesize_w'] . '" placeholder="' . $lang_block['imagesize_w'] . '" /><input type="size" class="form-control w100" name="config_imagesize_h" size="5" value="' . $data_block['imagesize_h'] . '" placeholder="' . $lang_block['imagesize_h'] . '" /></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        
        return $html;
    }

    function nv_block_config_news_view_cat_submit($module, $lang_block)
    {
        global $nv_Request;
        $return = array();
        $return['error'] = array();
        $return['config'] = array();
        $return['config']['catid'] = $nv_Request->get_array('config_catid', 'post', array());
        $return['config']['numrow'] = $nv_Request->get_int('config_numrow', 'post', 0);
        $return['config']['style'] = $nv_Request->get_title('config_style', 'post', 'mainleft');
        $return['config']['imagesize_w'] = $nv_Request->get_int('config_imagesize_w', 'post', 310);
        $return['config']['imagesize_h'] = $nv_Request->get_int('config_imagesize_h', 'post', 204);
        return $return;
    }

    function nv_block_news_view_cat($block_config)
    {
        global $nv_Cache, $module_array_cat, $module_info, $site_mods, $module_config, $global_config, $db;
        
        $module = $block_config['module'];
        $show_no_image = $module_config[$module]['show_no_image'];
        $blockwidth = $module_config[$module]['blockwidth'];
        
        if (empty($block_config['catid'])) {
            return '';
        }
        
        $catid = implode(',', $block_config['catid']);
        
        $db->sqlreset()
            ->select('id, catid, title, alias, homeimgfile, homeimgthumb, hometext, hitstotal, publtime')
            ->from(NV_PREFIXLANG . '_' . $site_mods[$module]['module_data'] . '_rows')
            ->where('status= 1 AND catid IN(' . $catid . ')')
            ->order('publtime DESC')
            ->limit($block_config['numrow']);
        $list = $nv_Cache->db($db->sql(), '', $module);
        
        if (! empty($list)) {
            if (file_exists(NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/news/block_news_view_cat.tpl')) {
                $block_theme = $global_config['module_theme'];
            } else {
                $block_theme = 'default';
            }
            
            $xtpl = new XTemplate('block_news_view_cat.tpl', NV_ROOTDIR . '/themes/' . $block_theme . '/modules/news');
            $xtpl->assign('NV_BASE_SITEURL', NV_BASE_SITEURL);
            $xtpl->assign('TEMPLATE', $block_theme);
            
            $style = $block_config['style'];

            $i = 0;
            foreach ($list as $l) {
                
                $l['link'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module . '&amp;' . NV_OP_VARIABLE . '=' . $module_array_cat[$l['catid']]['alias'] . '/' . $l['alias'] . '-' . $l['id'] . $global_config['rewrite_exturl'];
                $l['hometext_clean'] = strip_tags($l['hometext']);
                $l['hometext_clean'] = nv_clean60($l['hometext_clean'], 100, true);
				$l['publtime'] = nv_date( 'd/m/Y', $l['publtime']);
                if($i == 0){
                    if(!empty($l['homeimgfile']) and file_exists(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'])){
                        $l['thumb'] = nv_resize_crop_images(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'], $block_config['imagesize_w'], $block_config['imagesize_h'], $module);
                    }else{
                        $l['thumb'] = '';
                    }                  
                    $xtpl->assign('ROW', $l);
                    
                    if (! empty($l['thumb'])) {
                        $xtpl->parse('main.' . $style . '.newsmain1.image');
                    }
                    
                    $xtpl->parse('main.' . $style . '.newsmain1');
                    
                }else if($i == 1){
                    if(!empty($l['homeimgfile']) and file_exists(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'])){
                        $l['thumb'] = nv_resize_crop_images(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'], $block_config['imagesize_w'], $block_config['imagesize_h'], $module);
                    }else{
                        $l['thumb'] = '';
                    }                  
                    $xtpl->assign('ROW', $l);
                    
                    if (! empty($l['thumb'])) {
                        $xtpl->parse('main.' . $style . '.newsmain2.image');
                    }
                    
                    $xtpl->parse('main.' . $style . '.newsmain2');
                    
                }
				else{
					if(!empty($l['homeimgfile']) and file_exists(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'])){
                        $l['thumb'] = nv_resize_crop_images(NV_ROOTDIR . '/' . NV_UPLOADS_DIR . '/' . $site_mods[$module]['module_upload'] . '/' . $l['homeimgfile'], 60, 50, $module);
                    }else{
                        $l['thumb'] = '';
                    }                  
                    $xtpl->assign('ROW', $l);
                    
                    if (! empty($l['thumb'])) {
                        $xtpl->parse('main.' . $style . '.newssub.image');
                    }
                    $xtpl->assign('ROW', $l);
                    $xtpl->parse('main.' . $style . '.newssub');
                }
                $i++;
            }
            
            $xtpl->parse('main.' . $style);
            
            $xtpl->parse('main');
            return $xtpl->text('main');
        }
    }
}
if (defined('NV_SYSTEM')) {
    global $nv_Cache, $site_mods, $module_name, $global_array_cat, $module_array_cat;
    $module = $block_config['module'];
    if (isset($site_mods[$module])) {
        if ($module == $module_name) {
            $module_array_cat = $global_array_cat;
            unset($module_array_cat[0]);
        } else {
            $module_array_cat = array();
            $sql = 'SELECT catid, parentid, title, alias, viewcat, subcatid, numlinks, description, keywords, groups_view FROM ' . NV_PREFIXLANG . '_' . $site_mods[$module]['module_data'] . '_cat ORDER BY sort ASC';
            $list = $nv_Cache->db($sql, 'catid', $module);
            if (! empty($list)) {
                foreach ($list as $l) {
                    $module_array_cat[$l['catid']] = $l;
                    $module_array_cat[$l['catid']]['link'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module . '&amp;' . NV_OP_VARIABLE . '=' . $l['alias'];
                }
            }
        }
        $content = nv_block_news_view_cat($block_config);
    }
}
