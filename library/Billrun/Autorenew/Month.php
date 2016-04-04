<?php

/**
 * @package         Billing
 * @copyright       Copyright (C) 2012-2016 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero General Public License Version 4; see LICENSE.txt
 */

/**
 * Handle the logic of an auto renew monthly record.
 *
 * @author Tom Feigin
 */
class Billrun_Autorenew_Month extends Billrun_Autorenew_Record {
	
	/**
	 * Get the next renew date for this recurring plan.
	 * @return Next update date.
	 */
	protected function getNextRenewDate() {
		$lastDayNextMonth = strtotime('last day of next month 23:59:59');
		if($this->data['eom'] == 1) {
			return new MongoDate($lastDayNextMonth);
		}
		$nextMonth = strtotime("+1 month 23:59:59");
		if($nextMonth > $lastDayNextMonth) {
			return new MongoDate($lastDayNextMonth);
		} 
		$firstDayNextMonth = strtotime('first day of next month 23:59:59');
		if($nextMonth < $firstDayNextMonth) {
			return new MongoDate($firstDayNextMonth);
		} 
		
		// Check if the day of the start is larger, because of overlap.
		$fromDay = date('d',strtotime($this->data['from']));
		$dayDifference = $fromDay - date('d', $nextMonth);

		if($dayDifference <= 0) {
			$renewDate = $nextMonth;
		} else {
			$renewDate = strtotime("+$dayDifference days 23:59:59", $nextMonth);
		}
		return new MongoDate($renewDate);
	}
}
