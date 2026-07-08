const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();
const breakdown = {};
let totalLines = 0;

function walkDir(dir) {
    let files;
    try {
        files = fs.readdirSync(dir);
    } catch (e) {
        return;
    }
    for (const file of files) {
        const fullPath = path.join(dir, file);
        let stat;
        try {
            stat = fs.statSync(fullPath);
        } catch (e) {
            continue;
        }
        if (stat.isDirectory()) {
            walkDir(fullPath);
        } else if (stat.isFile()) {
            try {
                // Count lines by reading file and counting newlines
                const content = fs.readFileSync(fullPath, 'utf8');
                const lines = content.split(/\r?\n/).length;
                const ext = path.extname(fullPath).toLowerCase() || '(no extension)';
                breakdown[ext] = (breakdown[ext] || 0) + lines;
                totalLines += lines;
            } catch (e) {
                // For binaries or files that can't be decoded as utf8, read as raw buffer and count newlines
                try {
                    const content = fs.readFileSync(fullPath);
                    let lines = 0;
                    for (let i = 0; i < content.length; i++) {
                        if (content[i] === 10) { // '\n'
                            lines++;
                        }
                    }
                    lines++; // last line
                    const ext = path.extname(fullPath).toLowerCase() || '(no extension)';
                    breakdown[ext] = (breakdown[ext] || 0) + lines;
                    totalLines += lines;
                } catch (err) {}
            }
        }
    }
}

console.log('Counting lines in:', rootDir);
walkDir(rootDir);
console.log('Total Lines:', totalLines);
console.log('Breakdown:');
const sorted = Object.entries(breakdown).sort((a, b) => b[1] - a[1]);
for (const [ext, count] of sorted) {
    console.log(`${ext.padEnd(15)}: ${count}`);
}
