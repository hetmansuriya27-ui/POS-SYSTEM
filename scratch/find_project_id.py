import os

def main():
    workspace = r"c:\Users\star\Downloads\RestaurantProject-main"
    for root, dirs, files in os.walk(workspace):
        if 'node_modules' in root or '.git' in root or '.firebase' in root:
            continue
        for file in files:
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    if '6b2be' in content:
                        print(f"FOUND IN: {os.path.relpath(filepath, workspace)}")
            except:
                pass

if __name__ == '__main__':
    main()
