def main():
    css_path = r"c:\Users\star\Downloads\RestaurantProject-main\adminSide\css\styles.css"
    with open(css_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # print all blocks containing #layoutSidenav_content
    import re
    blocks = re.findall(r"[^\n]*#layoutSidenav_content[\s\S]*?\}", content)
    for b in blocks:
        print("BLOCK:")
        print(b)
        print("-" * 20)

if __name__ == '__main__':
    main()
