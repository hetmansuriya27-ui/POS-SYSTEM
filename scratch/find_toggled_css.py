def main():
    css_path = r"c:\Users\star\Downloads\RestaurantProject-main\adminSide\css\styles.css"
    with open(css_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
        
    for i, line in enumerate(lines):
        if 'sb-sidenav-toggled' in line:
            # print surrounding lines
            start = max(0, i - 5)
            end = min(len(lines), i + 8)
            print(f"--- Line {i+1} ---")
            for j in range(start, end):
                print(f"{j+1}: {lines[j].strip()}")

if __name__ == '__main__':
    main()
