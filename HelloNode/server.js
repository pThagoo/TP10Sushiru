const http = require('http&#39');
const server = http.createServer((req, res) => {
res.statusCode = 200;
res.setHeader('Content-Type', 'text/plain');
res.end('Servidor Node.js corriendo');
});
server.listen(3000, () => {
console.log('Servidor escuchando en el puerto 3000');
});