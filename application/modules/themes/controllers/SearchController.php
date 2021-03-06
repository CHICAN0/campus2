<?php

class SearchController extends JO_Action {
	
      public function indexAction() {
		$request = $this->getRequest();
		
		$this->getLayout()->meta_title = $this->translate('Search');
    	$this->getLayout()->meta_description = $this->translate('Search');
		
		if(method_exists($this, $request->getRequest('search') .'Action')) {
			$this->forward('search', $request->getRequest('search'));
		}
		
		$keyword = $request->getRequest('keyword');
		
		/* CRUMBS */
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Search')
			)
		);
		
		if($keyword) {
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			$order = $request->getRequest('order');
			if(is_null($order)) {
				$order = 'desc';
			}
			
			$sort = $request->getRequest('sort');
			if(is_null($sort)) {
				$sort = 'relevance';
			}
			
			if($sort == 'price') {
				$sort = 'rprice';
			}
			
			if($sort == 'username') {
				$prefix = 'users.';
			} else {
				$prefix = 'items.';
			}
			
			$keyword = trim(urldecode($keyword));
			$this->view->searchText = $keyword;
		
			$all_cats_link = $link = $request->getBaseUrl() .'?controller=search&keyword='. urlencode($keyword);
			
			$category = $request->getRequest('category');
			if($category) {
				$link .= '&category='. $category;
			}
			
			$this->view->sort_by = array(
				array(
					'name' => $this->view->translate('relevance'),
					'href' => WM_Router::create($link .'&sort=relevance'),
					'is_selected' => ($sort == 'relevance' ? true : false)
				),
				array(
					'name' => $this->view->translate('date'),
					'href' => WM_Router::create($link .'&sort=datetime'),
					'is_selected' => ($sort == 'datetime' ? true : false)
				),
				array(
					'name' => $this->view->translate('title'),
					'href' => WM_Router::create($link . '&sort=name'),
					'is_selected' => ($sort == 'name' ? true : false)
				),
				array(
					'name' => $this->view->translate('rating'),
					'href' => WM_Router::create($link . '&sort=rating'),
					'is_selected' => ($sort == 'rating' ? true : false)
				),
				array(
					'name' => $this->view->translate('sales'),
					'href' => WM_Router::create($link . '&sort=sales'),
					'is_selected' => ($sort == 'sales' ? true : false)
				),
				array(
					'name' => $this->view->translate('price'),
					'href' => WM_Router::create($link . '&sort=price'),
					'is_selected' => ($sort == 'price' ? true : false)
				),
				array(
					'name' => $this->view->translate('author name'),
					'href' => WM_Router::create($link . '&sort=username'),
					'is_selected' => ($sort == 'username' ? true : false)
				)
			);
		
			/* ORDER */
			$link .= '&sort='. $sort;
			$all_cats_link .= '&sort='. $sort;
			
			$this->view->orders = array(
				array(
					'name' => '&raquo;',
					'href' => WM_Router::create($link . '&order=desc'),
					'is_selected' => ($order == 'desc' ? true : false)
				),
				array(
					'name' => '&laquo;',
					'href' => WM_Router::create($link . '&order=asc'),
					'is_selected' => ($order == 'asc' ? true : false)
				)
			);
			
			$link .= '&order='. $order;
			$all_cats_link .= '&order='. $order;
			
			$total_records = Model_Search::getAllSearchItems($keyword, $sort .' '. $order, $category);
			
			if($total_records) {
				$this->view->total_result = $cnt_total_records = count($total_records);
				
				$this->view->categories = array();
				$categories = Model_Search::getCategories($keyword, $category);
				
				if($categories) {
					if($category) {
						$this->view->all_cats_link = WM_Router::create($all_cats_link);
						
						$exists = array();
						foreach($categories as $cat) {
							if(substr_count($cat['categories'], '|||') > 1) continue;
							
							$cat_parts = explode('|||', $cat['categories']);
							foreach($cat_parts as $part) {								
								$sub_parts = explode('@@@', $part);
								if(in_array($sub_parts[0], $exists)) continue;
								
								$exists[] = $sub_parts[0];
								
								$this->view->categories[] = array(
									'name' => $sub_parts[1],
									'href' => WM_Router::create($link .'&category='.$sub_parts[0])
								);
							}
						}
						
						$this->view->categories[0] = array(
							'name' => $this->view->categories[0]['name']
						);
					
						$this->view->cnt_categories = count($this->view->categories) - 1;
						$this->view->subcategories = $this->view->cnt_categories == 1 ? $this->translate('Subcategory') : $this->translate('Subcategories');

					} else {
						foreach($categories as $cat) {
							if(strpos($cat['categories'], '|||')) continue;
							
							$cat_parts = explode('@@@', $cat['categories']);
							
							$this->view->categories[] = array(
								'name' => $cat_parts[1],
								'href' => WM_Router::create($link .'&category='.$cat_parts[0])
							);
						}
						
						$this->view->cnt_categories = count($this->view->categories);
						$this->view->subcategories = $this->view->cnt_categories == 1 ? $this->translate('Category') : $this->translate('Categories');
					}
				}
				
				$start = (($page * $limit) - $limit);
				if($start > $cnt_total_records) {
					$page = max(ceil($cnt_total_records / $limit), 1);
					$start = (($page * $limit) - $limit);
				} elseif($start < 0) {
					$start = 0;
				}
				
				$items = array_slice($total_records, $start, $limit);
				$this->view->items = array();
			
		        foreach($items as $n => $item) {
		        	$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=preview&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name']));
					
					$item['categories'] = trim($item['categories'], ',');
					$item['categories'] = explode(',', $item['categories']);
					$item['categories'] = JO_Array::multi_array_to_single_uniq($item['categories']);
					$item['categories'] = array_filter($item['categories'], create_function('$a','return $a!="";'));

					$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
		        }
				
				
				$pagination = new Model_Pagination;
				$pagination->setLimit($limit);
				$pagination->setPage($page);
				$pagination->setText(array(
					'text_prev' => $this->view->translate('Prev'),
					'text_next' => $this->view->translate('Next')
				));
				$pagination->setTotal($cnt_total_records);
				$pagination->setUrl(WM_Router::create($link .'&page={page}'));
				$this->view->pagination = $pagination->render();
				
				if(!empty($this->view->pagination)) {
					$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
				}
			}
		}
		
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function attributesAction() {
		$request = $this->getRequest();
		$this->setViewChange('../search/index');
		
		$this->getLayout()->meta_title = $this->translate('Search');
    	$this->getLayout()->meta_description = $this->translate('Search');
		
		$attributes = array();
		$attributes[0] = $request->getRequest('attributes');
		$attributes[1] = $request->getRequest($attributes[0]);
		
		/* CRUMBS */
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Search')
			)
		);
		
		if(count($attributes) == 2) {
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			$order = $request->getRequest('order');
			if(is_null($order)) {
				$order = 'desc';
			}
			
			$sort = $request->getRequest('sort');
			if(is_null($sort)) {
				$sort = 'relevance';
			}
			
			if($sort == 'price') {
				$sort = 'rprice';
			}
			
			if($sort == 'username') {
				$prefix = 'users.';
			} else {
				$prefix = 'items.';
			}
			
			$this->view->searchText = implode(' - ', $attributes);
		
			$all_cats_link = $link = $request->getBaseUrl() .'?controller=search&action=attributes/'. urlencode($attributes[0] .'/'. urlencode($attributes[1]));
			
			$category = $request->getRequest('category');
			if($category) {
				$link .= '&category='. $category;
			}
			
			$this->view->sort_by = array(
				array(
					'name' => $this->view->translate('relevance'),
					'href' => WM_Router::create($link .'&sort=relevance'),
					'is_selected' => ($sort == 'relevance' ? true : false)
				),
				array(
					'name' => $this->view->translate('date'),
					'href' => WM_Router::create($link .'&sort=datetime'),
					'is_selected' => ($sort == 'datetime' ? true : false)
				),
				array(
					'name' => $this->view->translate('title'),
					'href' => WM_Router::create($link . '&sort=name'),
					'is_selected' => ($sort == 'name' ? true : false)
				),
				array(
					'name' => $this->view->translate('rating'),
					'href' => WM_Router::create($link . '&sort=rating'),
					'is_selected' => ($sort == 'rating' ? true : false)
				),
				array(
					'name' => $this->view->translate('sales'),
					'href' => WM_Router::create($link . '&sort=sales'),
					'is_selected' => ($sort == 'sales' ? true : false)
				),
				array(
					'name' => $this->view->translate('price'),
					'href' => WM_Router::create($link . '&sort=price'),
					'is_selected' => ($sort == 'price' ? true : false)
				),
				array(
					'name' => $this->view->translate('author name'),
					'href' => WM_Router::create($link . '&sort=username'),
					'is_selected' => ($sort == 'username' ? true : false)
				)
			);
		
			/* ORDER */
			$link .= '&sort='. $sort;
			$all_cats_link .= '&sort='. $sort;
			
			$this->view->orders = array(
				array(
					'name' => '&raquo;',
					'href' => WM_Router::create($link . '&order=desc'),
					'is_selected' => ($order == 'desc' ? true : false)
				),
				array(
					'name' => '&laquo;',
					'href' => WM_Router::create($link . '&order=asc'),
					'is_selected' => ($order == 'asc' ? true : false)
				)
			);
			
			$link .= '&order='. $order;
			$all_cats_link .= '&order='. $order;
			
			$total_records = Model_Search::getAllSearchItemsByAttr($attributes, $sort .' '. $order, $category);
			
			if($total_records) {
				$this->view->total_result = $cnt_total_records = count($total_records);
				
				$this->view->categories = array();
				$categories = Model_Search::getCategoriesByAttr($attributes, $category);
				
				if($categories) {
					if($category) {
						$this->view->all_cats_link = WM_Router::create($all_cats_link);
						
						$exists = array();
						foreach($categories as $cat) {
							if(substr_count($cat['categories'], '|||') > 1) continue;
							
							$cat_parts = explode('|||', $cat['categories']);
							foreach($cat_parts as $part) {								
								$sub_parts = explode('@@@', $part);
								if(in_array($sub_parts[0], $exists)) continue;
								
								$exists[] = $sub_parts[0];
								
								$this->view->categories[] = array(
									'name' => $sub_parts[1],
									'href' => WM_Router::create($link .'&category='.$sub_parts[0])
								);
							}
						}
						
						$this->view->categories[0] = array(
							'name' => $this->view->categories[0]['name']
						);
					
						$this->view->cnt_categories = count($this->view->categories) - 1;
						$this->view->subcategories = $this->view->cnt_categories == 1 ? $this->translate('Subcategory') : $this->translate('Subcategories');

					} else {
						foreach($categories as $cat) {
							if(strpos($cat['categories'], '|||')) continue;
							
							$cat_parts = explode('@@@', $cat['categories']);
							
							$this->view->categories[] = array(
								'name' => $cat_parts[1],
								'href' => WM_Router::create($link .'&category='.$cat_parts[0])
							);
						}
						
						$this->view->cnt_categories = count($this->view->categories);
						$this->view->subcategories = $this->view->cnt_categories == 1 ? $this->translate('Category') : $this->translate('Categories');
					}
				}
				
				$start = (($page * $limit) - $limit);
				if($start > $cnt_total_records) {
					$page = max(ceil($cnt_total_records / $limit), 1);
					$start = (($page * $limit) - $limit);
				} elseif($start < 0) {
					$start = 0;
				}
				
				$items = array_slice($total_records, $start, $limit);
				$this->view->items = array();
			
		        foreach($items as $n => $item) {
		        	$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=preview&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name']));
					
					$item['categories'] = trim($item['categories'], ',');
					$item['categories'] = explode(',', $item['categories']);
					$item['categories'] = JO_Array::multi_array_to_single_uniq($item['categories']);
					$item['categories'] = array_filter($item['categories'], create_function('$a','return $a!="";'));

					$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
		        }
				
				
				$pagination = new Model_Pagination;
				$pagination->setLimit($limit);
				$pagination->setPage($page);
				$pagination->setText(array(
					'text_prev' => $this->view->translate('Prev'),
					'text_next' => $this->view->translate('Next')
				));
				$pagination->setTotal($cnt_total_records);
				$pagination->setUrl(WM_Router::create($link .'&page={page}'));
				$this->view->pagination = $pagination->render();
				
				if(!empty($this->view->pagination)) {
					$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
				}
			}
		}
		
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
}