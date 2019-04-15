SELECT IndepYear, COUNT(*) cnt
FROM country
WHERE IndepYear IS NOT NULL
GROUP BY IndepYear
HAVING COUNT(*) > 10
ORDER BY IndepYear