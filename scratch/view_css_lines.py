def main():
    css_path = r"c:\Users\star\Downloads\RestaurantProject-main\adminSide\css\styles.css"
    with open(css_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
        
    for i in range(10910, 10940):
        print(f"{i+1}: {lines[i].strip()}")

if __name__ == '__main__':
    main()
