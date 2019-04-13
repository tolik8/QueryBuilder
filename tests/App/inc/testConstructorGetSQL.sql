SELECT kadry_id, COUNT(*) cnt
FROM PIKALKA.people
WHERE kadry_id = :id
GROUP BY kadry_id
HAVING COUNT(*) > 1
ORDER BY kadry_id