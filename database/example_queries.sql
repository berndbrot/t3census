-- Show unprocessed tweets
SELECT COUNT(t.tweet_id)
FROM twitter_tweet t JOIN twitter_url u ON (t.tweet_id = u.fk_tweet_id)
WHERE NOT t.tweet_processed;

-- Show stored CIDRs
SELECT cidr_id,INET_NTOA(cidr_ip),mask_to_cidr(INET_NTOA(cidr_mask)) AS cidr,created,cidr_description FROM cidr;

-- Show all discovered mapped IPs to CIDRs
SELECT INET_NTOA(s.server_ip) ip, INET_NTOA(c.cidr_mask & s.server_ip) network, INET_NTOA(c.cidr_mask) mask, c.cidr_description
FROM server s INNER JOIN cidr c ON (c.cidr_mask & s.server_ip) = c.cidr_ip;

-- Show top CIDR maintainers with TYPO3 installations
SELECT COUNT(h.host_id) AS num_hosts,c.cidr_description
FROM server s INNER JOIN cidr c ON ((c.cidr_mask & s.server_ip) = c.cidr_ip) LEFT JOIN host h ON (s.server_id = h.fk_server_id)
WHERE h.typo3_installed=1
GROUP BY c.cidr_description;
-- OR
SELECT COUNT(h.host_id) AS num_hosts,c.cidr_description
FROM server s RIGHT JOIN host h ON (s.server_id = h.fk_server_id) INNER JOIN cidr c ON ((c.cidr_mask & s.server_ip) = c.cidr_ip)
WHERE h.typo3_installed=1
GROUP BY c.cidr_description;


-- Process CIDR one-by-one starting from smallest subnet (hosts)
SELECT
	cidr_id,
	INET_NTOA(cidr_ip),
	mask_to_cidr(INET_NTOA(cidr_mask)) AS cidr,
	created,
	cidr_description
FROM cidr
WHERE updated IS NULL
ORDER BY cidr DESC
LIMIT 1;