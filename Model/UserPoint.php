<?php
App::uses('GivrateAppModel', 'Givrate.Model');

class UserPoint extends GivrateAppModel {

	public $validate = array(
		'user_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			)
		)
	);

	public $belongsTo = array(
		'User' => array(
			'className' => 'Users.User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	public $findMethods = array(
		'bestPoint' => true
	);

/**
 * countMyPoint method
 */
	public function countMyPoint($userId, $value, $type, $status) {
		$userPoint = $this->find('first', array(
			'conditions' => array(
				'UserPoint.user_id' => $userId,
				'UserPoint.type' => $type,
				'UserPoint.status' => $status,
			)
		));

		App::uses('PointUtil', 'Givrate.Utility');
		$this->Point = new PointUtil;
		if (empty($userPoint)) {
			$this->create();
			$data['UserPoint']['user_id'] = $userId;
			$data['UserPoint']['raters'] = 1;
			$data['UserPoint']['points'] = $value;
			$data['UserPoint']['type'] = $type;
			$data['UserPoint']['status'] = $status;
		} else {
			$this->id = $userPoint['UserPoint']['id'];
			$data['UserPoint']['raters'] = $userPoint['UserPoint']['raters'] + 1;
			$data['UserPoint']['points'] = $userPoint['UserPoint']['points'] + $value;
			$data['UserPoint']['type'] = $type;
			$data['UserPoint']['status'] = $status;
		}
		$avg = $this->Point->rateAvg($data['UserPoint']['points'], $data['UserPoint']['raters']);
		$data['UserPoint']['avg'] = $avg;

		$date = new	DateTime();
		$data['UserPoint']['point_date'] = $date->format('Y-m-d H:i:s');

		if ($this->save($data)) {
			return true;
		} else {
			return false;
		}
	}

/**
 * getMyPoint method
 */
	public function getMyPoint($userId, $status = null, $type = null) {
		$conditions = array(
			'UserPoint.user_id' => $userId
		);
		if (!empty($status)) {
			$conditions = Set::merge($conditions, array(
				'UserPoint.status' => $status
			));
		}
		if (!empty($type)) {
			$conditions = Set::merge($conditions, array(
				'UserPoint.type' => $type
			));
		}
		$mypoint = $this->find('first', array(
			'conditions' => $conditions
		));
		return $mypoint;
	}

/**
 * _findBestPoint method
 *
 * @return array
 */
	public function _findbestPoint($state, $query, $results = array()) {
		if ($state == 'before') {
			$status = isset($query['status']) ? $query['status'] : null;
			$type = isset($query['type']) ? $query['type'] : null;
			$pointLength = isset($query['pointLength']) ? $query['pointLength'] : null;

			if (!is_null($status)) {
				$query['conditions'] = Hash::merge(array(
					$this->escapeField('status') => $status
				), $query['conditions']);
			}
			if (!is_null($type)) {
				$query['conditions'] = Hash::merge(array(
					$this->escapeField('type') => $type
				), $query['conditions']);
			}
			if (!is_null($pointLength)) {
				$query['conditions'] = Hash::merge(array(
					$this->escapeField('points') . ' >' => $pointLength
				), $query['conditions']);
			}
			$query = Hash::merge(array(
				'order' => $this->escapeField('point_date') . ' DESC'
			), $query);
			return $query;
		} else {
			return $results;
		}
	}
}
