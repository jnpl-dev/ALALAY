export function grayscale(imageData) {
  const { data, width, height } = imageData
  const gray = new Uint8ClampedArray(width * height)
  for (let i = 0; i < data.length; i += 4) {
    gray[i / 4] = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2])
  }
  return { data: gray, width, height }
}

export function gaussianBlur5(gray, width, height) {
  const kernel = [
    1, 4, 7, 4, 1,
    4, 16, 26, 16, 4,
    7, 26, 41, 26, 7,
    4, 16, 26, 16, 4,
    1, 4, 7, 4, 1,
  ]
  const kSum = 273
  const half = 2
  const out = new Uint8ClampedArray(gray.length)
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      let sum = 0
      for (let ky = -half; ky <= half; ky++) {
        for (let kx = -half; kx <= half; kx++) {
          const px = Math.min(width - 1, Math.max(0, x + kx))
          const py = Math.min(height - 1, Math.max(0, y + ky))
          sum += gray[py * width + px] * kernel[(ky + half) * 5 + (kx + half)]
        }
      }
      out[y * width + x] = sum / kSum
    }
  }
  return out
}

export function sobel(gray, width, height) {
  const magnitude = new Float32Array(gray.length)
  const direction = new Float32Array(gray.length)
  const gxK = [-1, 0, 1, -2, 0, 2, -1, 0, 1]
  const gyK = [-1, -2, -1, 0, 0, 0, 1, 2, 1]
  for (let y = 1; y < height - 1; y++) {
    for (let x = 1; x < width - 1; x++) {
      let gx = 0, gy = 0
      for (let ky = -1; ky <= 1; ky++) {
        for (let kx = -1; kx <= 1; kx++) {
          const idx = (y + ky) * width + (x + kx)
          const g = gray[idx]
          const ki = (ky + 1) * 3 + (kx + 1)
          gx += g * gxK[ki]
          gy += g * gyK[ki]
        }
      }
      const i = y * width + x
      magnitude[i] = Math.sqrt(gx * gx + gy * gy)
      direction[i] = Math.atan2(gy, gx)
    }
  }
  return { magnitude, direction }
}

export function nonMaxSuppression(magnitude, direction, width, height) {
  const out = new Float32Array(magnitude.length)
  for (let y = 1; y < height - 1; y++) {
    for (let x = 1; x < width - 1; x++) {
      const i = y * width + x
      const angle = ((direction[i] * 180 / Math.PI) + 180) % 180
      let q = 0, r = 0
      if ((angle >= 0 && angle < 22.5) || (angle >= 157.5 && angle < 180)) {
        q = magnitude[y * width + (x + 1)]
        r = magnitude[y * width + (x - 1)]
      } else if (angle >= 22.5 && angle < 67.5) {
        q = magnitude[(y + 1) * width + (x - 1)]
        r = magnitude[(y - 1) * width + (x + 1)]
      } else if (angle >= 67.5 && angle < 112.5) {
        q = magnitude[(y + 1) * width + x]
        r = magnitude[(y - 1) * width + x]
      } else {
        q = magnitude[(y - 1) * width + (x - 1)]
        r = magnitude[(y + 1) * width + (x + 1)]
      }
      out[i] = (magnitude[i] >= q && magnitude[i] >= r) ? magnitude[i] : 0
    }
  }
  return out
}

export function doubleThreshold(magnitude, low = 30, high = 90) {
  const edges = new Uint8ClampedArray(magnitude.length)
  for (let i = 0; i < magnitude.length; i++) {
    if (magnitude[i] >= high) edges[i] = 2
    else if (magnitude[i] >= low) edges[i] = 1
    else edges[i] = 0
  }
  return edges
}

export function edgeTrackingByHysteresis(edges, width, height) {
  const out = new Uint8ClampedArray(edges.length)
  const visited = new Uint8Array(edges.length)
  const queue = []
  for (let i = 0; i < edges.length; i++) {
    if (edges[i] === 2) {
      queue.push(i)
      visited[i] = 1
    }
  }
  while (queue.length > 0) {
    const idx = queue.shift()
    out[idx] = 255
    const x = idx % width
    const y = Math.floor(idx / width)
    for (let dy = -1; dy <= 1; dy++) {
      for (let dx = -1; dx <= 1; dx++) {
        if (dx === 0 && dy === 0) continue
        const nx = x + dx
        const ny = y + dy
        if (nx < 0 || nx >= width || ny < 0 || ny >= height) continue
        const ni = ny * width + nx
        if (!visited[ni] && edges[ni] >= 1) {
          visited[ni] = 1
          queue.push(ni)
        }
      }
    }
  }
  return out
}

export function cannyEdgeDetection(imageData, low = 30, high = 90) {
  const gray = grayscale(imageData)
  const blurred = gaussianBlur5(gray.data, gray.width, gray.height)
  const { magnitude, direction } = sobel(blurred, gray.width, gray.height)
  const suppressed = nonMaxSuppression(magnitude, direction, gray.width, gray.height)
  const thresholded = doubleThreshold(suppressed, low, high)
  const edges = edgeTrackingByHysteresis(thresholded, gray.width, gray.height)
  return { edges, width: gray.width, height: gray.height }
}

export function findDocumentCorners(edgeData, width, height) {
  const points = []
  const step = 2
  for (let y = 0; y < height; y += step) {
    for (let x = 0; x < width; x += step) {
      if (edgeData[y * width + x] === 255) {
        points.push({ x, y })
      }
    }
  }
  if (points.length < 10) return null

  const cx = points.reduce((s, p) => s + p.x, 0) / points.length
  const cy = points.reduce((s, p) => s + p.y, 0) / points.length

  function angle(p) { return Math.atan2(p.y - cy, p.x - cx) }
  points.sort((a, b) => angle(b) - angle(a))

  function distance(a, b) { return Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2) }

  const topLeft = points.reduce((best, p) =>
    (p.x + p.y) < (best.x + best.y) ? p : best
  )
  const topRight = points.reduce((best, p) =>
    (p.x - p.y) > (best.x - best.y) ? p : best
  )
  const bottomRight = points.reduce((best, p) =>
    (p.x + p.y) > (best.x + best.y) ? p : best
  )
  const bottomLeft = points.reduce((best, p) =>
    (p.x - p.y) < (best.x - best.y) ? p : best
  )

  const corners = [topLeft, topRight, bottomRight, bottomLeft]
  const minDist = Math.min(
    distance(topLeft, topRight), distance(topRight, bottomRight),
    distance(bottomRight, bottomLeft), distance(bottomLeft, topLeft),
  )

  for (let i = 0; i < 4; i++) {
    const cluster = points.filter(p => distance(p, corners[i]) < minDist * 0.3)
    if (cluster.length > 0) {
      corners[i] = {
        x: Math.round(cluster.reduce((s, p) => s + p.x, 0) / cluster.length),
        y: Math.round(cluster.reduce((s, p) => s + p.y, 0) / cluster.length),
      }
    }
  }

  return corners
}

export function orderCorners(pts) {
  const cx = pts.reduce((s, p) => s + p.x, 0) / 4
  const cy = pts.reduce((s, p) => s + p.y, 0) / 4
  return pts.sort((a, b) => Math.atan2(a.y - cy, a.x - cx) - Math.atan2(b.y - cy, b.x - cx))
}

function computeHomography(src, dst) {
  const A = []
  const b = []
  for (let i = 0; i < 4; i++) {
    const sx = src[i].x, sy = src[i].y
    const dx = dst[i].x, dy = dst[i].y
    A.push([sx, sy, 1, 0, 0, 0, -dx * sx, -dx * sy])
    A.push([0, 0, 0, sx, sy, 1, -dy * sx, -dy * sy])
    b.push(dx, dy)
  }
  const h = solveLinearSystem(A, b)
  if (!h) return null
  return (x, y) => {
    const denom = h[6] * x + h[7] * y + 1
    return {
      x: (h[0] * x + h[1] * y + h[2]) / denom,
      y: (h[3] * x + h[4] * y + h[5]) / denom,
    }
  }
}

function solveLinearSystem(A, b) {
  const n = 8
  const aug = A.map((row, i) => [...row, b[i]])
  for (let col = 0; col < n; col++) {
    let maxRow = col
    for (let row = col + 1; row < n; row++) {
      if (Math.abs(aug[row][col]) > Math.abs(aug[maxRow][col])) maxRow = row
    }
    if (Math.abs(aug[maxRow][col]) < 1e-12) return null
    ;[aug[col], aug[maxRow]] = [aug[maxRow], aug[col]]
    const pivot = aug[col][col]
    for (let j = col; j <= n; j++) aug[col][j] /= pivot
    for (let row = 0; row < n; row++) {
      if (row !== col) {
        const factor = aug[row][col]
        for (let j = col; j <= n; j++) aug[row][j] -= factor * aug[col][j]
      }
    }
  }
  return aug.map(row => row[n])
}

export function correctPerspective(sourceCanvas, corners, targetWidth, targetHeight, margin = 40) {
  const ordered = orderCorners(corners)
  const dst = [
    { x: margin, y: margin },
    { x: targetWidth - 1 - margin, y: margin },
    { x: targetWidth - 1 - margin, y: targetHeight - 1 - margin },
    { x: margin, y: targetHeight - 1 - margin },
  ]
  const map = computeHomography(dst, ordered)
  if (!map) return sourceCanvas

  const srcCtx = sourceCanvas.getContext('2d')
  const srcData = srcCtx.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height)

  const outCanvas = document.createElement('canvas')
  outCanvas.width = targetWidth
  outCanvas.height = targetHeight
  const outCtx = outCanvas.getContext('2d')
  const outData = outCtx.createImageData(targetWidth, targetHeight)
  const out = outData.data
  const src = srcData.data
  const sw = sourceCanvas.width
  const sh = sourceCanvas.height

  for (let y = 0; y < targetHeight; y++) {
    for (let x = 0; x < targetWidth; x++) {
      const p = map(x, y)
      if (p.x < 0 || p.x >= sw - 1 || p.y < 0 || p.y >= sh - 1) continue
      const ix = Math.floor(p.x)
      const iy = Math.floor(p.y)
      const fx = p.x - ix
      const fy = p.y - iy
      const idx = iy * sw + ix
      const si = idx * 4
      for (let c = 0; c < 4; c++) {
        const tl = src[si + c]
        const tr = src[si + 4 + c]
        const bl = src[si + sw * 4 + c]
        const br = src[si + sw * 4 + 4 + c]
        const top = tl + (tr - tl) * fx
        const bot = bl + (br - bl) * fx
        out[(y * targetWidth + x) * 4 + c] = Math.round(top + (bot - top) * fy)
      }
    }
  }
  outCtx.putImageData(outData, 0, 0)
  return outCanvas
}

export function magicFilter(canvas) {
  const ctx = canvas.getContext('2d')
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
  const { data, width, height } = imageData
  const len = data.length
  const gray = new Uint8Array(width * height)

  for (let i = 0; i < len; i += 4) {
    gray[i / 4] = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2])
  }

  let minVal = 255, maxVal = 0
  for (let i = 0; i < gray.length; i++) {
    if (gray[i] < minVal) minVal = gray[i]
    if (gray[i] > maxVal) maxVal = gray[i]
  }
  const range = maxVal - minVal
  const stretched = new Uint8Array(gray.length)
  if (range > 0) {
    for (let i = 0; i < gray.length; i++) {
      stretched[i] = Math.round(((gray[i] - minVal) / range) * 255)
    }
  } else {
    stretched.set(gray)
  }

  const blurred = gaussianBlur5(stretched, width, height)

  const sharpened = new Uint8ClampedArray(data.length)
  const amount = 1.2
  for (let i = 0; i < gray.length; i++) {
    const sharpVal = stretched[i] + amount * (stretched[i] - blurred[i])
    const v = Math.max(0, Math.min(255, Math.round(sharpVal)))
    sharpened[i * 4] = v
    sharpened[i * 4 + 1] = v
    sharpened[i * 4 + 2] = v
    sharpened[i * 4 + 3] = 255
  }

  const blockSize = 30
  const C = 8
  const integral = new Uint32Array((width + 1) * (height + 1))
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const idx = (y + 1) * (width + 1) + (x + 1)
      const val = stretched[y * width + x]
      integral[idx] = val
        + integral[(y) * (width + 1) + (x + 1)]
        + integral[(y + 1) * (width + 1) + (x)]
        - integral[(y) * (width + 1) + (x)]
    }
  }

  const output = new Uint8ClampedArray(data.length)
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const x1 = Math.max(0, x - Math.floor(blockSize / 2))
      const y1 = Math.max(0, y - Math.floor(blockSize / 2))
      const x2 = Math.min(width - 1, x + Math.floor(blockSize / 2))
      const y2 = Math.min(height - 1, y + Math.floor(blockSize / 2))
      const count = (x2 - x1 + 1) * (y2 - y1 + 1)
      const sum = integral[(y2 + 1) * (width + 1) + (x2 + 1)]
        - integral[(y1) * (width + 1) + (x2 + 1)]
        - integral[(y2 + 1) * (width + 1) + (x1)]
        + integral[(y1) * (width + 1) + (x1)]
      const mean = sum / count
      const idx = (y * width + x) * 4
      const sv = stretched[y * width + x]
      const threshold = sv < (mean - C) ? 0 : 255
      const enhanced = sharpened[idx]
      const blended = threshold === 0
        ? Math.max(0, enhanced - 30)
        : Math.min(255, enhanced + 10)
      output[idx] = blended
      output[idx + 1] = blended
      output[idx + 2] = blended
      output[idx + 3] = 255
    }
  }

  const outCanvas = document.createElement('canvas')
  outCanvas.width = width
  outCanvas.height = height
  const outCtx = outCanvas.getContext('2d')
  outCtx.putImageData(new ImageData(output, width, height), 0, 0)
  return outCanvas
}

export function downscaleCanvas(source, maxWidth = 1200) {
  const scale = Math.min(1, maxWidth / Math.max(source.width, source.height))
  const dest = document.createElement('canvas')
  dest.width = Math.round(source.width * scale)
  dest.height = Math.round(source.height * scale)
  const ctx = dest.getContext('2d')
  ctx.imageSmoothingEnabled = true
  ctx.imageSmoothingQuality = 'high'
  ctx.drawImage(source, 0, 0, dest.width, dest.height)
  return dest
}
