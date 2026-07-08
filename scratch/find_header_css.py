def main():
    css_path = r"c:\Users\star\Downloads\RestaurantProject-main\customerSide\css\style.css"
    with open(css_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find all rules related to header, nav-bar, brand, nav-list
    import re
    keywords = ['#header', '.header', '.nav-bar', '.brand', '.nav-list', '.navbar']
    lines = content.split('\n')
    for kw in keywords:
        print(f"=== Matches for {kw} ===")
        for i, line in enumerate(lines):
            if kw in line:
                start = max(0, i - 2)
                end = min(len(lines), i + 8)
                print(f"--- Line {i+1} ---")
                for j in range(start, end):
                    print(f"{j+1}: {lines[j].strip()}")

if __name__ == '__main__':
    main()
