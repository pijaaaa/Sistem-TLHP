import { useEffect, useRef } from 'react'

interface CaptchaCanvasProps {
  text: string
  width?: number
  height?: number
}

export const CaptchaCanvas = ({ text, width = 280, height = 100 }: CaptchaCanvasProps) => {
  const canvasRef = useRef<HTMLCanvasElement>(null)

  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    // Clear canvas
    ctx.clearRect(0, 0, width, height)

    // Background with more complex gradient
    const gradient = ctx.createLinearGradient(0, 0, width, height)
    gradient.addColorStop(0, '#e8f5e9')
    gradient.addColorStop(0.25, '#f1f8f4')
    gradient.addColorStop(0.5, '#ffffff')
    gradient.addColorStop(0.75, '#f0f7f4')
    gradient.addColorStop(1, '#e0f2e9')
    ctx.fillStyle = gradient
    ctx.fillRect(0, 0, width, height)

    // Add subtle grid pattern
    ctx.strokeStyle = 'rgba(26, 77, 60, 0.03)'
    ctx.lineWidth = 1
    for (let i = 0; i < width; i += 20) {
      ctx.beginPath()
      ctx.moveTo(i, 0)
      ctx.lineTo(i, height)
      ctx.stroke()
    }
    for (let i = 0; i < height; i += 20) {
      ctx.beginPath()
      ctx.moveTo(0, i)
      ctx.lineTo(width, i)
      ctx.stroke()
    }

    // Add multiple random curves for complexity
    for (let i = 0; i < 4; i++) {
      ctx.strokeStyle = `rgba(${Math.random() * 100 + 50}, ${Math.random() * 150 + 50}, ${Math.random() * 100 + 50}, ${0.2 + Math.random() * 0.2})`
      ctx.lineWidth = 1 + Math.random() * 2
      ctx.beginPath()
      
      const startX = Math.random() * width
      const startY = Math.random() * height
      ctx.moveTo(startX, startY)
      
      for (let j = 0; j < 3; j++) {
        const cpX1 = Math.random() * width
        const cpY1 = Math.random() * height
        const cpX2 = Math.random() * width
        const cpY2 = Math.random() * height
        const endX = Math.random() * width
        const endY = Math.random() * height
        ctx.bezierCurveTo(cpX1, cpY1, cpX2, cpY2, endX, endY)
      }
      ctx.stroke()
    }

    // Draw text with advanced effects FIRST
    const chars = text.split('')
    const spacing = width / (chars.length + 1.5)
    
    chars.forEach((char, index) => {
      ctx.save()
      
      // Position calculation with wave effect
      const baseX = spacing * (index + 1)
      const waveOffset = Math.sin(index * 0.8) * 8
      const x = baseX + waveOffset + (Math.random() - 0.5) * 12
      const y = height / 2 + (Math.random() - 0.5) * 15
      const rotation = (Math.random() - 0.5) * 0.5
      
      ctx.translate(x, y)
      ctx.rotate(rotation)
      
      // Font styling with variation
      const fontSize = 36 + Math.random() * 10
      const fontWeight = Math.random() > 0.5 ? 'bold' : '900'
      ctx.font = `${fontWeight} ${fontSize}px 'Arial Black', Arial, sans-serif`
      
      // Multiple shadow layers for depth
      ctx.shadowColor = 'rgba(0, 0, 0, 0.3)'
      ctx.shadowBlur = 4
      ctx.shadowOffsetX = 3
      ctx.shadowOffsetY = 3
      
      // Create gradient color for each character
      const charGradient = ctx.createLinearGradient(-20, -20, 20, 20)
      const hue1 = 140 + Math.random() * 40
      const hue2 = 160 + Math.random() * 40
      charGradient.addColorStop(0, `hsl(${hue1}, 60%, 25%)`)
      charGradient.addColorStop(0.5, `hsl(${hue2}, 70%, 20%)`)
      charGradient.addColorStop(1, `hsl(${hue1}, 65%, 30%)`)
      
      // Draw character with stroke
      ctx.fillStyle = charGradient
      ctx.fillText(char, 0, 0)
      
      // Add stroke for better visibility
      ctx.strokeStyle = 'rgba(0, 0, 0, 0.4)'
      ctx.lineWidth = 1.5
      ctx.strokeText(char, 0, 0)
      
      ctx.restore()
    })

    // NOW add interference lines OVER the text
    for (let i = 0; i < 5; i++) {
      ctx.strokeStyle = `rgba(${Math.random() * 150 + 50}, ${Math.random() * 150 + 50}, ${Math.random() * 150 + 50}, ${0.25 + Math.random() * 0.2})`
      ctx.lineWidth = 1.5 + Math.random() * 2
      ctx.beginPath()
      
      for (let x = 0; x < width; x += 2) {
        const y = height / 2 + Math.sin(x / (8 + i * 4)) * (12 + i * 6) + (Math.random() - 0.5) * 15
        if (x === 0) {
          ctx.moveTo(x, y)
        } else {
          ctx.lineTo(x, y)
        }
      }
      ctx.stroke()
    }

    // Add random dots OVER the text
    for (let i = 0; i < 100; i++) {
      const x = Math.random() * width
      const y = Math.random() * height
      const radius = Math.random() * 2.5
      ctx.fillStyle = `rgba(${Math.random() * 200 + 55}, ${Math.random() * 200 + 55}, ${Math.random() * 200 + 55}, ${0.25 + Math.random() * 0.35})`
      ctx.beginPath()
      ctx.arc(x, y, radius, 0, Math.PI * 2)
      ctx.fill()
    }

    // Add colorful noise dots OVER the text
    for (let i = 0; i < 80; i++) {
      const x = Math.random() * width
      const y = Math.random() * height
      const radius = 0.8 + Math.random() * 2
      const hue = Math.random() * 360
      ctx.fillStyle = `hsla(${hue}, 70%, 50%, ${0.3 + Math.random() * 0.25})`
      ctx.beginPath()
      ctx.arc(x, y, radius, 0, Math.PI * 2)
      ctx.fill()
    }

    // Add diagonal lines pattern
    ctx.strokeStyle = 'rgba(26, 77, 60, 0.08)'
    ctx.lineWidth = 1
    for (let i = -height; i < width; i += 15) {
      ctx.beginPath()
      ctx.moveTo(i, 0)
      ctx.lineTo(i + height, height)
      ctx.stroke()
    }

  }, [text, width, height])

  return (
    <div className="relative">
      <canvas
        ref={canvasRef}
        width={width}
        height={height}
        className="rounded-xl border-2 border-gray-300 shadow-lg bg-white"
      />
      {/* Decorative corner elements */}
      <div className="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-[#1a4d3c] rounded-tl-xl" />
      <div className="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-[#1a4d3c] rounded-tr-xl" />
      <div className="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-[#1a4d3c] rounded-bl-xl" />
      <div className="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-[#1a4d3c] rounded-br-xl" />
    </div>
  )
}
