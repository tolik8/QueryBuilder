SELECT code, name, population
FROM country 
WHERE region = :region
ORDER BY name