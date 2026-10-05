INSERT INTO `{DB_PREFIX}notification_settings` (`event`,`category`,`enable_email`,`enable_web`,`enable_slack`,`notify_to_team`,`notify_to_team_members`,`notify_to_terms`,`sort`,`deleted`)
SELECT 'operations_approval_revoked','operations',1,1,0,'','','operations_recipients',200,0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{DB_PREFIX}notification_settings` ns WHERE ns.event='operations_approval_revoked');
