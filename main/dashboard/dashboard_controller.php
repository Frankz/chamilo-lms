<?php
/* For licensing terms, see /license.txt */

/**
 * Controller script. Prepares the common background
 * variables to give to the scripts corresponding to
 * the requested action
 * @author Christian Fasanando <christian1827@gmail.com>
 * @todo move to main/inc/lib
 * @package chamilo.dashboard
 */
class DashboardController
{
    private $user_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_id = api_get_user_id();
    }

    /**
     * Display blocks from dashboard plugin paths
     * render to dashboard.php view
     */
    public function display()
    {
        $user_id = $this->user_id;
        $dashboard_blocks = DashboardManager::get_enabled_dashboard_blocks();
        $user_block_data  = DashboardManager::get_user_block_data($user_id);
        $user_blocks_id = array_keys($user_block_data);
        $blocks = null;
        if (!empty($dashboard_blocks)) {
            foreach ($dashboard_blocks as $block) {
                // display only user blocks
                if (!in_array($block['id'], $user_blocks_id)) {
                    continue;
                }

                $path = $block['path'];
                $controller_class = $block['controller'];
                $filename_controller = $path.'.class.php';
                $dashboard_plugin_path = api_get_path(SYS_PLUGIN_PATH).'dashboard/'.$path.'/';
                require_once $dashboard_plugin_path.$filename_controller;
                if (class_exists($controller_class)) {
                    $obj = new $controller_class($user_id);

                    // check if user is allowed to see the block
                    if (method_exists($obj, 'is_block_visible_for_user')) {
                        $is_block_visible_for_user = $obj->is_block_visible_for_user($user_id);
                        if (!$is_block_visible_for_user) {
                            continue;
                        }
                    }

                    $blocks[$path] = $obj->get_block();
                    // set user block column
                    $blocks[$path]['column'] = $user_block_data[$block['id']]['column'];
                }
            }
        }

        $view = isset($_GET['view']) ? $_GET['view'] : 'blocks';
        api_block_anonymous_users();
        $link_blocks_view = $link_list_view = null;
        if ($view == 'list') {
            $link_blocks_view = '<a href="'.api_get_self().'?view=blocks">'.
                Display::return_icon('blocks.png', get_lang('DashboardBlocks'), '', ICON_SIZE_MEDIUM).'</a>';
        } else {
            $link_list_view = '<a href="'.api_get_self().'?view=list">'.
                Display::return_icon('edit.png', get_lang('EditBlocks'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        $configuration_link = null;
        if (api_is_platform_admin()) {
            $configuration_link = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins">'
                .Display::return_icon('settings.png', get_lang('ConfigureDashboardPlugin'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        $content = '<div class="actions">';
        $content .= $link_blocks_view.$link_list_view.$configuration_link;
        $content .= '</div>';

        // block dashboard view
        if (isset($view) && $view == 'blocks') {
            if (isset($blocks) && count($blocks) > 0) {
                $columns = [];
                // group content html by number of column
                if (is_array($blocks)) {
                    $tmp_columns = [];
                    foreach ($blocks as $block) {
                        $tmp_columns[] = $block['column'];
                        if (in_array($block['column'], $tmp_columns)) {
                            $columns['column_'.$block['column']][] = $block['content_html'];
                        }
                    }
                }

                $content .= '<div id="columns" class="row">';
                if (count($columns) > 0) {
                    $columns_name = array_keys($columns);
                    // blocks for column 1
                    if (in_array('column_1', $columns_name)) {
                        $content .= '<div id="column1" class="col-md-6">';
                        foreach ($columns['column_1'] as $data) {
                            $content .= $data;
                        }
                        $content .= '</div>';
                    } else {
                        $content .= '<div id="column1" class="col-md-6">';
                        $content .= '&nbsp;';
                        $content .= '</div>';
                    }
                    // blocks for column 2
                    if (in_array('column_2', $columns_name)) {
                        // blocks for column 1
                        $content .= '<div id="column2" class="col-md-6">';
                        foreach ($columns['column_2'] as $data) {
                            $content .= $data;
                        }
                        $content .= '</div>';
                    } else {
                        $content .= '<div id="column2" class="col-md-6">';
                        $content .= '&nbsp;';
                        $content .= '</div>';
                    }
                }
                $content .= '</div>';
            } else {
                $content .= '<div style="margin-top:20px;">'.get_lang('YouHaveNotEnabledBlocks').'</div>';
            }
        } else {
            $user_id = api_get_user_id();
            $content .= DashboardManager::display_user_dashboard_list($user_id);
        }

        $tpl = new Template(get_lang('Dashboard'));
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }

    /**
     * This method allow store user blocks from dashboard manager
     * render to dashboard.php view
     */
    public function store_user_block()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
            $enabled_blocks = $_POST['enabled_blocks'];
            $columns = $_POST['columns'];
            DashboardManager::store_user_blocks($this->user_id, $enabled_blocks, $columns);
            Display::addFlash(Display::return_message(get_lang('Saved')));
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'dashboard/index.php');
        exit;
    }

    /**
     * This method is used when you close a block from dashboard block interface
     * render to dashboard.php view
     */
    public function close_user_block($path)
    {
        DashboardManager::close_user_block($this->user_id, $path);
        Display::addFlash(Display::return_message(get_lang('Saved')));
        header('Location: '.api_get_path(WEB_CODE_PATH).'dashboard/index.php');
        exit;
    }
}
