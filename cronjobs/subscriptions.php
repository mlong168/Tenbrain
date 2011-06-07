<?php
define("_CRONJOB_",true);
require("/home/yvershynin/tenbrain/www/index.php");

	//look through all entries of account_role_exp table and create all needed changes.
	
	$account_role_exp = new Application_Model_AccountRoleExp();
	
	//$select = $account_role_exp->select()->where("");
	
	$account_roles = $account_role_exp->fetchAll();
	
	$log_file = fopen("/home/yvershynin/cron.log","a+");
	
	$today = strtotime(date("Y-m-d"));
	foreach($account_roles as $account_role)
	{
		fwrite($log_file, date("H:i:s Y-m-d")." : id: ".$account_role->id." exp date: ".$account_role->expiration_date."\n");
		if(strtotime($account_role->expiration_date) < $today)
		{
			fwrite($log_file, "\t".$account_role->id." : Delete row \n");
			$where = $account_role_exp->getAdapter()->quoteInto("id = ?", $account_role->id);
			$account_role_exp->delete($where);
			fwrite($log_file, "\t".$account_role->id." : Account role deleted. \n");
		}
		else if((strtotime($account_role->expiration_date) - $today)/(3600*24) == 3)
		{
			//send alert email
			fwrite($log_file, "\t".$account_role->id." : Send alert mail to account_id: ".$account_role->account_id." \n");
			$email_subject = "Tenbrain account expiration notification.";
			$email_body = "Hello, \n\nYour account at Tenbrin will expire in 3 days at ".date("d M Y", strtotime($account_role->expiration_date))."\n";
			$email_body .= "If you want to extend this period, please visit us at http://tenbrain.com \n\n";
			$email_body .= "Best Regards,\nTenbrain Team.";
			
			$account = new Application_Model_DbTable_Accounts();
			
			$select = $account->select()->where("id = ?", $account_role->account_id);
	
			$user_account = $account->fetchRow($select);
			
			$to_email = $user_account->email;
			
			if(mail($to_email, $email_subject, $email_body))
			{
				fwrite($log_file, "\t".$account_role->id." : Email sent to ".$to_email." \n");
			}
			else
			{
				fwrite($log_file, "\t".$account_role->id." : Email NOT sent \n");
			}
		}
	}
	
	fclose($log_file);