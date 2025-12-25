<?php
require_once 'init.php';

// --- CONFIGURAÇÃO DA LANDING PAGE ---
// Se acesso direto à raiz (sem classe), mostra home.html
if (empty($_GET) && empty($_POST)) {
    if (file_exists('home.html')) {
        echo file_get_contents('home.html');
        exit; 
    }
}
// ------------------------------------

$ini = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : []);

new TSession;
ApplicationAuthenticationService::checkMultiSession();
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

if ( TSession::getValue('logged') )
{
    if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
    {
        $content = file_get_contents("app/templates/{$theme}/iframe.html");
    }
    else
    {
        $content = file_get_contents("app/templates/{$theme}/layout.html");
        
        // ============================================================
        // LÓGICA DE TROCA DE MENU (ADMIN vs MÉDICO)
        // ============================================================
        $menu_file = 'menu.xml'; // Padrão (Admin, Técnicos, etc)
        
        $user_groups = TSession::getValue('usergroup_ids');
        $id_grupo_medico = 5; // ID Confirmado do grupo Médicos

        if (!empty($user_groups) && is_array($user_groups)) {
            // Normaliza o array para buscar tanto na chave quanto no valor
            // Isso resolve o problema de array(5 => 5) ou array(0 => 5)
            $todos_ids = [];
            foreach($user_groups as $key => $val) {
                $todos_ids[] = $key;
                $todos_ids[] = $val;
            }
            
            // Verifica se o ID 5 está presente
            if (in_array($id_grupo_medico, $todos_ids)) {
                $menu_file = 'menu-medic.xml';
            }
        }
        // ============================================================

        // Parseia o menu escolhido acima
        $content = str_replace('{MENU}', AdiantiMenuBuilder::parse($menu_file, $theme), $content);
        $content = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top.xml', $theme), $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom.xml', $theme), $content);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        $content = file_get_contents("app/templates/{$theme}/public.html");
        $menu    = AdiantiMenuBuilder::parse('menu-public.xml', $theme);
        $content = str_replace('{MENU}', $menu, $content);
        $content = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top-public.xml', $theme), $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);
    }
    else
    {
        $content = file_get_contents("app/templates/{$theme}/login.html");
    }
}

$content = ApplicationTranslator::translateTemplate($content);
$content = AdiantiTemplateParser::parse($content);

echo $content;

if (TSession::getValue('logged') OR $public)
{
    if ($class)
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        if (!empty($ini['general']['public_entry']))
        {
            AdiantiCoreApplication::loadPage($ini['general']['public_entry'], '', $_REQUEST);
        }
    }
    else
    {
        AdiantiCoreApplication::loadPage('LoginForm', '', $_REQUEST);
    }
}