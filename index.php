<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * MaxSite CMS
 * (c) http://max-3000.com/
 * Andrey Grevtsov
 * (c) http://aradm.ru
 */

#Требуется php_ldap

# функция автоподключения плагина
function adauth_autoload()
{
	$options = mso_get_option('plugin_adauth', 'plugins', array());
	$widget_flogin_priority = (isset($options['widget_flogin_priority'])) ? $options['widget_flogin_priority'] : 10; 
	mso_hook_add('init', 'adauth_init');
	mso_hook_add('content', 'adauth_content_parse'); # хук на админку
	mso_hook_add('login_form_auth', 'adauth_auth_login_form_auth', $widget_flogin_priority); # хук на форму логина
//	mso_hook_add('admin_init', 'adauth_admin_init'); # хук на админку
//	mso_hook_add( 'head', 'adauth_head');
}

# функция выполняется при активации (вкл) плагина
function adauth_activate($args = array())
{	
	mso_create_allow('adauth_edit', t('Админ-доступ к настройкам ADauth', 'plugins') . ' ' . t('&laquo;Авторизация Active Directory&raquo;', __FILE__));
	return $args;
}

# функция выполняется при деинсталяции плагина
function adauth_uninstall($args = array())
{	
	mso_delete_option('plugin_adauth', 'plugins'); // удалим созданные опции
	mso_remove_allow('adauth_edit'); // удалим созданные разрешения
//	mso_delete_option_mask('adauth_widget', 'plugins'); // 
	return $args;
}

# подключим страницу опций, как отдельную ссылку
function adauth_admin_init($args = array()) 
{
    
	if ( mso_check_allow('adauth_edit') ) 
	{
		$this_plugin_url = 'plugin_options/adauth'; // url и hook
		mso_admin_menu_add('plugins', $this_plugin_url, t('ADauth', 'plugins'));
		mso_admin_url_hook ($this_plugin_url, 'plugin_adauth');
	}
	
	return $args;
}

# функция отрабатывающая миниопции плагина (function плагин_mso_options)
# если не нужна, удалите целиком
function adauth_mso_options() 
{
	
	if ( !mso_check_allow('adauth_edit') ) 
	{
		echo t('Доступ запрещен', 'plugins');
		return;
	}

	global $MSO;
	$CI = & get_instance();
	
	$CI->db->select('groups_id, groups_name');
	$q = $CI->db->get('groups');
	$groups = array();
	$options_group='';	
  	foreach ($q->result_array() as $rw){
		$options_group .= $rw['groups_id'] . "||" . $rw['groups_name'] . '#';
	}
	mso_admin_plugin_options('plugin_adauth', 'plugins', 
		array(
				'ldap_server' => array(
						'type' => 'text', 
						'name' => 'Адрес сервера LDAP:', 
						'description' => 'Укажите доменный или IP-адрес контроллера домена Active Directory',
						'default' => '127.0.0.1'
					),	
				'ldap_user_dn' => array(
						'type' => 'text', 
						'name' => 'DN пользователя домена для доступа к каталогу LDAP:', 
						'description' => 'DistinguishedName пользователя домена, от имени которого maxsite будет выполнять запрос для получения информации автоматически регистриуемого пользователя',
						'default' => 'CN=maxsite,CN=users,dc=domain,dc=ru'
					),					
				'ldap_password' => array(
						'type' => 'text', 
						'name' => 'Пароль пользователя домена для доступа к каталогу LDAP:', 
						'description' => 'Пароль пользователя домена, от имени которого maxsite будет выполнять запрос для получения информации автоматически регистриуемого пользователя',
						'default' => 'superrepuspassssap'
					),	
					
				'ldap_dn' => array(
						'type' => 'text', 
						'name' => 'База DN для поиска в LDAP:', 
						'description' => 'DistinguishedName контейнера, в котором находятся пользователи.',
						'default' => 'dc=domain,dc=ru'
					),	
				'ldap_filter' => array(
						'type' => 'text', 
						'name' => 'Фильтр для поиска в LDAP:', 
						'description' => 'С помощью фильтра можно ограничить область поиска определённым OU или каким-то аттрибутом, например, членством в группе.',
						'default' => '(&(objectClass=user)(objectCategory=person))'
					),	
														
				'maxsite_group' => array(
						'type' => 'select', 
						'name' => t('Группа maxsite для регистрируемого пользователя:', __FILE__), 
						'description' => 'Группа maxsite, в которую включается автоматически зарегистрированный пользователь. Для этой группы должны быть даны права &laquo;edit_self_users&raquo;',
						'values' => $options_group,
						'default' => '2'
					),		
					
				'mail_domain' => array(
						'type' => 'text', 
						'name' => 'Почтовый домен:', 
						'description' => 'Если в реквизитах пользователя домена не указан его E-Mail, то адрес формируется автоматически из имени пользователя и указанного в этом поле домена.',
						'default' => 'mail.to'
					),
				'ldap_groups_mapping' => array(
						'type' => 'textarea', 
						'name' => t('Псевдонимы ADauth для групп LDAP', 'plugins'),
						'description' => t('Вводите сопоставление для групп LDAP в формате: <pre>Псевдоним | DistinguishedName группы LDAP</pre> Используйте конструкцию <pre>[adauth=псевдоним_группы]...[/adauth]</pre> для вывода защищённого содержания.', 'plugins'),
						'default' => ''
					),					
				'autologin' => array(
						'type' => 'checkbox', 
						'name' => t('Автоматический вход на Maxsite', 'plugins'),
						'description' => t('Входить на Maxsite автоматически с использованием доменной авторизации.', 'plugins'),
						'default' => '1'
					),
			),
		'Настройки плагина ADauth', // титул
		'Авторизация на сайте с использованием возможностей веб-сервера и данных в Active Directory. Пользователи, учётные записи которых существуют в AD, при входе на maxsite автоматически получают на нём регистрацию и авторизуются с использованием реквизитов в домене Windows'   // инфо
	);	
	
}

function adauth_head($args = array())
{
	return $args;
}

function adauth_get_remote_user() {
  if (isset($_SERVER['REMOTE_USER']))  return $_SERVER['REMOTE_USER'];
  return getenv('REMOTE_USER');
}

function adauth_init($arg = array())
{
	if ($_POST 	and isset($_POST['flogin_user'])) return; // Пользователь входит не под доменной учётной записью
	if (adauth_get_remote_user()) {
		$user = adauth_get_remote_user();
		adauth_login($user);
    }
}
function adauth_login($user){
	global $MSO;
	
	$CI = & get_instance();
    $redirect_url = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : getinfo('siteurl');
    
	if (isset($CI->session->userdata['userlogged']) and $CI->session->userdata['userlogged'] )
	{
		// залогинен 
		if (is_type('logout')) {
			$CI->session->set_userdata('user_autologin', false);
			$CI->session->set_userdata('userlogged', false);		  
//			mso_redirect(getinfo('siteurl')."login",true);
		}
		return;
	}
//echo $CI->session->userdata['user_autologin'];
//echo $CI->session->userdata['userlogged'];
	$options = mso_get_option('plugin_adauth', 'plugins', array());
	$autologin = isset($options['autologin']) ? $options['autologin'] : false; 

	$CI->session->set_userdata('user_autologin', isset($CI->session->userdata['user_autologin']) and $CI->session->userdata['user_autologin']!='' ?$CI->session->userdata['user_autologin']:$autologin);
 
    if ($CI->session->userdata['user_autologin'] or (is_type('login') and mso_segment(2)=='adauth'))
	{	
		$CI->session->set_userdata('user_autologin', true);
		if (!adauth_check_user($user))
//		echo "create";
       	{// Создание пользователя, если он не зарегистрирован на maxsite
			require_once( getinfo('common_dir') . 'functions-edit.php' ); // функции редактирования
			$data = adauth_get_ldap_user_info($user);		
			if ($data)
			{
				$result = mso_new_user($data,false);
				if (isset($result['result'])) 
				{
					if ($result['result'] == 1)
					{
//						echo '<div class="update">' . t('Пользователь создан!', 'admin') . '</div>'; // . $result['description'];
						adauth_check_user($user);
						$data['users_id'] = $CI->session->userdata['users_id'];
						$result = mso_edit_user($data);
//						echo '<div class="update">' . t('Пользователь создан!', 'admin') . '</div>'. $result['description'];
					}
//					else 
//						echo '<div class="error">' . t('Произошла ошибка, user: ' . $user . ' mail: ' .  $data['users_email'] , 'admin') . '<p>' . $result['description'] . '</p></div>';
				}
			}
		}
		$CI->session->set_userdata('user_logged', true);
		mso_redirect($redirect_url,true);
	}
	else
	{

    }
}
function adauth_get_ldap_user_info($user) {
	$options = mso_get_option('plugin_adauth', 'plugins', array());
	$ldap_server = isset($options['ldap_server']) ? $options['ldap_server'] : '';
	$ldap_user_dn = isset($options['ldap_user_dn']) ? $options['ldap_user_dn'] : '';
	$ldap_password = isset($options['ldap_password']) ? $options['ldap_password'] : '';
	$ldap_dn = isset($options['ldap_dn']) ? $options['ldap_dn'] : '';
	$ldap_filter =  isset($options['ldap_filter']) ? $options['ldap_filter'] : "(&(objectClass=user)(objectCategory=person))";
	$ldap_filter = "(&$ldap_filter(sAMAccountName=$user))";
	$ldap_attr = array("displayName","sn","givenName","sAMAccountName","mail","description","title","department","company");
    $mail_domain = isset($options['mail_domain']) ? $options['mail_domain'] : 'mail.ru';
    
	$ldap_connection = ldap_connect($ldap_server);
	ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
	ldap_set_option($ldap_connection,LDAP_OPT_REFERRALS,0);
	
	$data=false;
	
	if ($ldap_connection and $ldap_server != '' and $ldap_user_dn  != '' and $ldap_password != '' and $ldap_dn != '' ) {
		$ldap_bind_res = ldap_bind($ldap_connection,$ldap_user_dn,$ldap_password);
		if ($ldap_bind_res)
		{
			$result = ldap_search($ldap_connection,$ldap_dn,$ldap_filter,$ldap_attr);
			if (ldap_count_entries($ldap_connection,$result) > 0)
			{
				$result_entries = ldap_get_entries($ldap_connection,$result);
				ldap_unbind($ldap_connection);
				$user_email = isset($result_entries[0]['mail'][0]) ? $result_entries[0]['mail'][0] : "$user@$mail_domain";
				$user_family = isset($result_entries[0]['sn'][0]) ? $result_entries[0]['sn'][0] : '';
				$user_name = isset($result_entries[0]['givenname'][0]) ? $result_entries[0]['givenname'][0] : '';
				$user_nik = ($user_name !='' or $user_family != '') ? "$user_name $user_family" : $user;
				$user_title = isset($result_entries[0]['title'][0]) ? $result_entries[0]['title'][0] : '';
				$user_company = isset($result_entries[0]['company'][0]) ? $result_entries[0]['company'][0] : '';
				$user_department = isset($result_entries[0]['department'][0]) ? $result_entries[0]['department'][0] : '';
				$user_description = isset($result_entries[0]['description'][0]) ? $result_entries[0]['description'][0] : '' ;
				$user_description = "$user_company\n$user_department\n$user_title\n$user_description";
				$password=adauth_generate_random_string(20);
		
				$data = array(
					'user_login' => $user,
					'password' => mso_md5($password),
					'users_login' => $user,
					'users_nik' => $user_nik,
					'users_email' => $user_email,
					'users_password' => $password,
					'users_groups_id' => '2',
					'users_first_name' => $user_name,
					'users_last_name' => $user_family,
					'users_description' => $user_description,
					'users_time_zone' => '14400',
					'users_language' => 'ru'
				);
			}
		}
	}
	return $data;
}
function adauth_check_user($user) {
	global $MSO;
	$CI = & get_instance();
			$CI->db->from('users'); # таблица users
			$CI->db->select('*'); # все поля
			$CI->db->limit(1); # одно значение
			
			$CI->db->where('users_login', $user); // where 'users_login' = $user
			
			$query = $CI->db->get();
			
			if ($query->num_rows() > 0) # есть такой юзер
			{
				$userdata = $query->result_array();
				
				# добавляем юзера к сессии
				$CI->session->set_userdata('userlogged', '1');
				
				$data = array(
					'users_id' => $userdata[0]['users_id'],
					'users_nik' => $userdata[0]['users_nik'],
					'users_login' => $userdata[0]['users_login'],
					'users_password' => $userdata[0]['users_password'],
					'users_groups_id' => $userdata[0]['users_groups_id'],
					'users_last_visit' => $userdata[0]['users_last_visit'],
					'users_show_smiles' => $userdata[0]['users_show_smiles'],
					'users_time_zone' => $userdata[0]['users_time_zone'],
					'users_language' => $userdata[0]['users_language'],
					// 'users_levels_id' => $userdata[0]['users_levels_id'],
					// 'users_avatar_url' => $userdata[0]['users_avatar_url'],
					// 'users_skins' => $userdata[0]['users_skins']
				);

				$CI->session->set_userdata($data);
				
				// сразу же обновим поле последнего входа
				$CI->db->where('users_id', $userdata[0]['users_id']);
				$CI->db->update('users', array('users_last_visit'=>date('Y-m-d H:i:s')));
				return true;
			}
			else return false;
					
}
function adauth_generate_random_string($length){
	$randstr = "";
	for($i=0; $i<$length; $i++){
		$randnum = mt_rand(0,61);
		if($randnum < 10){
			$randstr .= chr($randnum+48);
		}else if($randnum < 36){
		$randstr .= chr($randnum+55);
		}else{
			$randstr .= chr($randnum+61);
		}
	}
	return $randstr;
} 
function adauth_content_check ($m) 
{
	$CI = & get_instance();

	if (isset($CI->session->userdata['users_login']) )
	{
		if (adauth_check_ldap_user_group($CI->session->userdata['users_login'],$m[1]) ) 
		{
			return $m[2];
		} 
		else 
		{
			return ;//'Защищённое содержание доступно только группе ' . $m[1];
		}		
	}
	return;
}

function adauth_content_parse($text) 
{
	$preg = '~\[adauth=(.*?)\](.*?)\[\/adauth\]~si';
	$text = preg_replace_callback($preg, "adauth_content_check" , $text);
	return $text;
}

function adauth_check_ldap_user_group($user,$adauth_group) {
	$CI = & get_instance();
	
	if (!isset($CI->session->userdata['userlogged']) or !$CI->session->userdata['userlogged'] ) return false;

	$options = mso_get_option('plugin_adauth', 'plugins', array());
	$ldap_filter_memberof = '';
	if (isset($options['ldap_groups_mapping']) and $options['ldap_groups_mapping'] != '') 
	{
		$ldap_groups_mapping = explode("\n", trim($options['ldap_groups_mapping']));
	}
	else return false;
	
	foreach ($ldap_groups_mapping as $ldap_groups_mapping_line)
	{
		$lgm = explode('|', $ldap_groups_mapping_line);
		if (isset($lgm[0]) and trim($lgm[0]) == $adauth_group)
		{
			$ldap_filter_memberof = $lgm[1];
		}
	}
	if ($ldap_filter_memberof == '') return false;
	
	$ldap_server = isset($options['ldap_server']) ? $options['ldap_server'] : '';
	$ldap_user_dn = isset($options['ldap_user_dn']) ? $options['ldap_user_dn'] : '';
	$ldap_password = isset($options['ldap_password']) ? $options['ldap_password'] : '';
	$ldap_dn = isset($options['ldap_dn']) ? $options['ldap_dn'] : '';
	$ldap_filter =  isset($options['ldap_filter']) ? $options['ldap_filter'] : "(&(objectClass=user)(objectCategory=person))";
	$ldap_filter = "(&$ldap_filter(sAMAccountName=$user)(memberOf=$ldap_filter_memberof))";
	$ldap_attr = array("sAMAccountName");

	$ldap_connection = ldap_connect($ldap_server);
	ldap_set_option($ldap_connection,LDAP_OPT_PROTOCOL_VERSION,3);
	ldap_set_option($ldap_connection,LDAP_OPT_REFERRALS,0);
	
	$is_member=false;
	
	if ($ldap_connection and $ldap_server != '' and $ldap_user_dn  != '' and $ldap_password != '' and $ldap_dn != '' ) {

		$ldap_bind_res = ldap_bind($ldap_connection,$ldap_user_dn,$ldap_password);
		if ($ldap_bind_res)
		{
			$result = ldap_search($ldap_connection,$ldap_dn,$ldap_filter,$ldap_attr);
			if (ldap_count_entries($ldap_connection,$result) > 0)
			{
				ldap_unbind($ldap_connection);
				$is_member = true;
			}
		}
	}
	return $is_member;
}
function adauth_auth_login_form_auth($text = '') 
{
	$text .= '<p class="adauth" style="font-weight:bold;"><a href="'.getinfo('siteurl').'login/adauth">доменные службы Active Directory</a></p>';
	return $text;
}
