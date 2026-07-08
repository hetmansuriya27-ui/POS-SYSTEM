import re

def main():
    db_path = r"c:\Users\star\Downloads\RestaurantProject-main\restaurantDB.txt"
    with open(db_path, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        
    print("=== MEMBERSHIPS TABLE ===")
    match = re.search(r"CREATE TABLE `memberships` ([\s\S]*?);", content, re.IGNORECASE)
    if match:
        print(match.group(0))
        
    inserts = re.findall(r"INSERT INTO `memberships` VALUES\s*([\s\S]*?);", content, re.IGNORECASE)
    for ins in inserts:
        print(ins.strip()[:1000])

if __name__ == '__main__':
    main()
