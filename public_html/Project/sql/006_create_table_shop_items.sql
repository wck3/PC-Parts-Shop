CREATE TABLE IF NOT EXISTS Shop_Items(
    id int AUTO_INCREMENT PRIMARY  KEY, -- table identified by item id
    name varchar(30) UNIQUE,  -- unique name for each item
    description text,       
    category varchar(15),
    stock int DEFAULT  0,   
    image text, -- this col type can't have a default value; this isn't required for any project, I chose to add it for mine
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    unit_price int DEFAULT  99999, -- my cost is int because I don't have regular currency; shop people may want to record it as pennies
    visibility binary, -- default is 1 (true)

    check (stock >= 0), -- don't allow negative stock; I don't allow backorders
    check (unit_price >= 0) -- don't allow negative costs
);