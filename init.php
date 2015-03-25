<?php defined('SYSPATH') or die('No direct access allowed.');

Observer::observe('frontpage_requested', function($request_uri) {
	$server_uri = $_SERVER['REQUEST_URI'];
	$server_uri = trim(parse_url($server_uri, PHP_URL_PATH), '/');

	$ctx = Context::instance()->set('url_segments', preg_split('/\//', $request_uri, -1, PREG_SPLIT_NO_EMPTY));

	if (!empty($server_uri) AND strlen(URL_SUFFIX) > 0 AND Config::get('site', 'check_url_suffix') == Config::YES)
	{
		$request_uri = $request_uri . URL_SUFFIX;

		if ($server_uri !== $request_uri)
		{
			$ctx->throw_404();
		}
	}
});

Observer::observe('frontpage_found', function($page) {
	if ($page->is_password_protected())
	{
		throw new HTTP_Exception_Front_401('Page protected');
	}
});

// Update page last-modified date
Observer::observe('widget_set_location', function($page_ids) {
	ORM::factory('page')->set_update_date($page_ids);
});

Observer::observe( array('page_add_after_save', 'page_edit_after_save'), function($page) {
	if (!empty($page->behavior_id))
	{
		$data = Request::current()->post('behavior');
		ORM::factory('Page_Behavior_Setting')
			->find_by_page($page)
			->set_page($page)
			->set('data', $data)
			->save();
	}
	else
	{
		$model = ORM::factory('Page_Behavior_Setting')
				->find_by_page($page);

		if ($model->loaded())
		{
			$model->delete();
		}
	}
});

Observer::observe('modules::after_load', function() {
	Behavior::init();
});

Observer::observe(array('controller_before_page_edit', 'controller_before_page_add'), function() {
	Assets::js('controller.behavior', ADMIN_RESOURCES . 'js/controller/behavior.js', 'global');
});