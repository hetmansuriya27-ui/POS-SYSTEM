import os

def main():
    dir_path = r"c:\Users\star\Downloads\RestaurantProject-main\adminSide\panel"
    for file in os.listdir(dir_path):
        if file.endswith(('.html', '.php')):
            filepath = os.path.join(dir_path, file)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    if 'id="layoutSidenav_content"' in content or "id='layoutSidenav_content'" in content:
                        # find line containing it
                        for line in content.split('\n'):
                            if 'layoutSidenav_content' in line:
                                print(f"{file}: {line.strip()}")
            except:
                pass

if __name__ == '__main__':
    main()
