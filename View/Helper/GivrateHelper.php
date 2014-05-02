<?php
App::uses('PointUtil', 'Givrate.Utility');

class GivrateHelper extends AppHelper {

	public $helpers = array('Html', 'Form', 'Js', 'Session');

	protected $_Rating = null;
	protected $_RateCalculate = null;

	public function beforeRender($viewFile) {
		$params = $this->_View->params;
		if (isset($params['isAjax']) && $params['isAjax'] === true) {
			return;
		}
		if (isset($params['admin']) && $params['admin'] === true) {
			return;
		}
		$this->_Rating = ClassRegistry::init('Givrate.Rating');
		$this->_RateCalculate = ClassRegistry::init('Givrate.RateCalculate');
		$this->Point = new PointUtil;

		$this->Html->css('/givrate/css/style', null, array('inline' => false));
		$this->Html->script(array(
			'/givrate/js/givrate'), array('inline' => false
		));
	}

/*
 * Givrate::displayPoint
 *
 * Display point Rating or Vote
 * @token: array
 * @ownerId: value of user_id from your content
 * @options:
 *	- ratelink: display rating action link. Default false.
 *	- votelink: display vote action link. Default false.
 *  - display: display of your point type. Default 'rating'.
 *
 * Array @options can be combined with the @options Givrate::star and Givrate::vote
 */
	public function displayPoint($token, $ownerId, $options = array()) {
		if (empty($token['id']) || empty($ownerId)) {
			return __d('givrate', 'Empty Token or ownerId.');
		}
		$options = Set::merge(array(
			'ratelink' => false,
			'votelink' => false,
			'status' => 'default',
			'display' => 'rating',
		), $options);
		$display = $options['display'];
		unset($options['display']);
		$result = $this->_RateCalculate->getPoint($token['token'], $display, $options['status'], array('recursive' => -1));
		switch($display) {
			case 'vote':
				$field = 'point';
				if ($options['votelink'] == true) {
					$link = $this->vote($token, $ownerId, $options);
				} else {
					$link = '';
				}
				$point = empty($result['RateCalculate'][$field]) ? 0 : $result['RateCalculate'][$field];
			break;
			case 'rating':
			default:
				$field = 'avg';
				if ($options['ratelink'] == true) {
					$link = $this->star($token, $ownerId, $options);
				} else {
					$link = '';
				}
				$point = empty($result['RateCalculate'][$field]) ? 0 : number_format($result['RateCalculate'][$field], 1);
			break;
		}
		unset($options['ratelink']);
		unset($options['votelink']);

		$point = empty($result['RateCalculate'][$field]) ? 0 : $point;
		$istar = $this->Html->tag('i', '', array('class' => 'flaticon-outline'));
		$point = $this->Html->div('avg text-left', $istar . $this->Html->tag('span',$point, array('class' => 'point'))) . $link;
		return $point;
	}

/*
 * Givrate::star
 *
 * @token: array
 * @ownerId: value of user_id from your content
 * @options:
 *	- stars : value of the stars.
 *	- title : tooltip title for each stars.
 *	- status: status name of your rating. default value is "default"
 *	- upoint : Create user point record. Default "false"
 */
	public function star($token, $ownerId, $options = array()) {
		if (empty($token['id'])) {
			return __d('givrate', 'Empty token!');
		}

		$id = $token['foreign_key'];
		$recursive = array('recursive' => -1);
		$js = 'javascript:void(0);';
		$options = Set::merge(array(
			'class' => 'rating',
			'link' => true,
			'value' => 0,
			'stars' => 5,
			'upoint' => '',
		), $options);

		if (isset($options['status']) && !empty($options['status'])) {
			$status = $options['status'];
		} else {
			$status = 'default';
		}
		$options = Set::merge(array('data-status' => $status), $options);
		$options = Set::merge($options, array('data-id' => 's'.$ownerId));

		if (isset($options['title'])) {
			if ($options['title'] != false) {
				$title = array_combine(range(1, count($options['title'])), array_values($options['title']));

				$titleCount = count($title);
				if ($titleCount != $options['stars']) {
					return __d('givrate', 'Value star title is not the same as the value stars');
				}
			} else {
				$title = array();
			}
		} else {
			$title = array(1 => 'bad', 'good enough', 'good', 'awesome', 'amazing');
		}

		$stars = null;
		for ($i = 1; $i <= $options['stars']; $i++) {
			$link = null;
			$options = Set::merge($options, array(
				'class' => 'rate-link-'.$id,
				'data-token' => $token['token'],
				'data-rating' => 's'.$i,
				'data-type' => 'rating',
				'escape' => false,
			));
			if ($title != array()) {
				$options = Set::merge($options, array('title' => $title[$i]));
			}
			$link = $this->Html->link('&nbsp;', $js, $options);
			$stars .= $this->Html->tag('li', $link, array('class' => 'star' . $i));
$script =<<<EOF
$(window).load(Givrate.Ratings.list($id));
$('body').on('click', '.rate-link-$id', Givrate.Ratings.star);
EOF;
		}

		$authId = $this->Session->read('Auth.User.id');
		$checking = $this->_Rating->checking($token['token'], $authId, 'rating', $status, $ownerId, $recursive);
		if (!empty($checking)) {
			$result = $this->_RateCalculate->getPoint($token['token'], 'rating', $status, $recursive);
			$currentStars = $this->Point->currentStars($result['RateCalculate']['avg'], $result['RateCalculate']['point'], $result['RateCalculate']['count']);
			$maxWidth = 18 * $options['stars'];
$script =<<<EOF
$('div.stars').css('max-width', $maxWidth);
$('ul.rating').css({'width': $currentStars + 'px', 'background-position' : '0px 72px'});
$('.stars .rating li a').css({'display': 'none'});
EOF;
		}

		$stars = $this->Html->div('stars', $this->Html->tag('ul', $stars, array('class' => 'rating')));
		$this->Js->buffer($script);
		if (!$authId) {
			return;
		} else {
			return $stars;
		}
	}

/*
 * Givrate::vote
 *
 * @token: value
 * @ownerId: value
 *
 * OwnerId = value user_id of their content
 *
 * @options:
 *	- img: custom image. Default null
 *	- height: height for image if "img" options is not empty.
 *	- width: width for image if "img" options is not empty.
 *	- alt: alt for image if "img" options is not empty.
 *	- text: Title for vote link if don`t want to use image.
 *	- upoint : Create user point record. Default "false"
 *	- status : status name of your vote. Default value is "default"
 */
	public function vote($token, $ownerId, $options = array()) {
		if (empty($token['id'])) {
			return __d('givrate', 'Empty Token!');
		}
		$id = $token['foreign_key'];
		$js = 'javascript:void(0);';
		$options = Set::merge(array(
			'class' => 'voted-' . $id,
			'data-token' => $token['token'],
			'upoint' => '',
			'vote' => '1',
			'data-type' => 'vote',
			'title' => 'vote',
			'img' => '',
			'width' => '',
			'height' => '',
			'alt' => '',
			'escape' => false,
		), $options);

		if (!empty($options['status'])) {
			$options = Set::merge(array('data-status' => $options['status']), $options);
		} else {
			$options['data-status'] = 'default';
		}

		$options = Set::merge($options, array('data-vote' => 's'.$options['vote']));
		unset($options['vote']);

		$options = Set::merge($options, array('data-id' => 's'.$ownerId));
		if (!empty($options['img'])) {
			$value = $this->Html->image($options['img'], array(
				'width' => $options['width'],
				'height' => $options['height'],
				'alt' => $options['alt']
			));
		} else {
			$value = 'vote';
		}

		if (isset($options['text'])) {
			$value = $options['text'];
		}
		unset($options['img']);
		unset($options['height']);
		unset($options['width']);
		unset($options['alt']);
		unset($options['text']);
		if (isset($options[0])) {
			unset($options[0]);
		}

		$link = $this->Html->link($value, $js, $options);
$script =<<<EOF
$('body').on('click', '.voted-$id', Givrate.Ratings.vote);
EOF;
		$this->Js->buffer($script);
		return $this->Html->div('vote', $this->Html->tag('span', $link));
	}

}
