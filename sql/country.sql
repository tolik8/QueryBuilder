SELECT Code, Name, Population
FROM country 
WHERE region = :region
ORDER BY Name