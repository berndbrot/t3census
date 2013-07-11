SELECT
	INET_NTOA(s.server_ip)               ip,
	INET_NTOA(c.cidr_mask & s.server_ip) network,
	INET_NTOA(c.cidr_mask)               mask,
	c.cidr_description
FROM server s INNER JOIN cidr c
		ON (c.cidr_mask & s.server_ip) = c.cidr_ip;

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