import os

def main():
    dir_path = r"c:\Users\star\Downloads\RestaurantProject-main\adminSide"
    for root, dirs, files in os.walk(dir_path):
        for file in files:
            if file.endswith(('.html', '.php', '.js', '.css')):
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                        content = f.read()
                        if 'sb-sidenav-toggled' in content:
                            print(f"FOUND IN: {os.path.relpath(filepath, dir_path)}")
                except:
                    pass

if __name__ == '__main__':
    main()
