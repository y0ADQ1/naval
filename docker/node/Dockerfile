FROM node:18-alpine

WORKDIR /app

# Copia todo el proyecto para acceder a los archivos correctos
COPY . .

# Instala dependencias
RUN npm ci

# Compila los assets en modo producción
RUN npm run build

# Asegúrate de que la salida sea visible
RUN ls -la public/build || echo "Build directory not created!"
RUN cat public/build/manifest.json || echo "No manifest file!"

CMD ["echo", "Assets generados con éxito"]