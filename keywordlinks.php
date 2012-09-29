<?php

/*------------------------------------------------------------------------
# plg_keywordlinks
# ------------------------------------------------------------------------
# author &nbsp; &nbsp;Buyanov Danila - Saity74 Ltd.
# copyright Copyright (C) 2012 saity74.ru. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.saity74.ru
# Technical Support: &nbsp; http://saity74.ru/keywordlinks.html
# Admin E-mail: admin@saity74.ru
-------------------------------------------------------------------------*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
//setlocale(LC_ALL, 'ru_RU.CP1251', 'rus_RUS.CP1251', 'Russian_Russia.1251', 'ru_RU.UTF-8'); 

jimport('joomla.plugin.plugin');

class plgContentKeyWordLinks extends JPlugin 
{

	protected $keywords;
	protected $nofollow;
	protected $target;
	protected $htags;
	protected $limit;
	protected $args;
	protected $_blocks;
	protected $counter;
	
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		$this->counter = 0;
		
		$this->keywords = trim($this->params->get('keywords'));
		
		if (!$this->keywords)
			return true;
			
		$this->nofollow 	= $this->params->get('nofollow');
		$this->target 		= $this->params->get('target');
		$this->htags 		= $this->params->get('htags', 1);
		$this->limit		= $this->params->get('limit', 1);
		$this->title		= $this->params->get('title', 1);
		$this->relative		= $this->params->get('relative', 1);
		
		if ($this->relative)
		{
			$host = JFactory::getURI()->getHost();
		}
	
		
		$matches = explode("\n", $this->keywords);
		
		$this->args = array();
		$this->args['rel'] = !$this->nofollow  ? 0 : 'nofollow';
		$this->args['target'] = !$this->target ? '_parent' : '_blank';
		
		$args = '';
		foreach ($this->args as $key => $value)
		{
			if ($value)
				$args .= $key.'="'.$value.'" ';
		}
		
		//save links
		$regex = '#<a(.*?)>(.*?)</a>#';
		$article->text = preg_replace_callback($regex, array(&$this, '_excludeLink'), $article->text);
			
		if ($this->htags)
		{
			$regex = '#<h(.*?)>(.*?)</h.{1}>#';
			$article->text = preg_replace_callback($regex, array(&$this, '_excludeHtag'), $article->text);
		}
		
		foreach ($matches as $match)
		{
			list($keyword, $href) = explode('|', $match);
			
			$regex = array('#\s'.$keyword.'\s#', '#\b'.$keyword.'\b#');
			
			if (strpos($href, $host) !== false)
			{
				//relative link
				$this->link = ' <a href="'.$href.'" '.($this->title? 'title="'.$keyword.'"' : '').'>'.$keyword.'</a> ';
			} else {
				//external link
				$this->link = ' <a href="'.$href.'" '.$args.' '.($this->title? 'title="'.$keyword.'"' : '').'>'.$keyword.'</a> ';
			}
			
			$article->text = preg_replace_callback($regex, array(&$this, '_excludeKeyword'), $article->text, $this->limit);
		}
		
		if (is_array($this->_blocks) && !empty($this->_blocks))
		{
			foreach ($this->_blocks as $block)
			{
				list($type, $value) = $block;
				$regex = '#<!-- keywordlink-excluded-'.$type.' -->#';
				$article->text = preg_replace($regex, $value, $article->text, 1);
			}
		}
			
		return true;

	}
	
	protected function _excludeLink($matches)
	{
		$this->_blocks[] = array('link', $matches[0]);
		return '<!-- keywordlink-excluded-link -->';
	}
	
	protected function _excludeHtag($matches)
	{
		$this->_blocks[] = array('htag', $matches[0]);
		return '<!-- keywordlink-excluded-htag -->';
	}
	
	protected function _excludeKeyword($matches)
	{
		$this->counter++;
		$this->_blocks[] = array('keyword-'.$this->counter, $this->link);
		return '<!-- keywordlink-excluded-keyword-'.$this->counter.' -->';
	}
}

?>


