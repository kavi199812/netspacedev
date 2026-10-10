// API client helper for fetching data from PHP / MySQL backend

export function getApiBaseUrl() {
  if (typeof window !== 'undefined') {
    // Check if running on localhost dev server
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      return 'http://localhost/netspacedev/api';
    }
    // Production on Hostinger domain
    return '/api';
  }
  // Server-side / build time in local Node
  return 'http://localhost/netspacedev/api';
}

// Fallback sample data in case MySQL/Apache is not running during build
export const FALLBACK_PROJECTS = [
  {
    id: 1,
    title: 'Cloud-Native FinTech Platform',
    slug: 'cloud-native-fintech-platform',
    summary: 'High-throughput distributed banking and payment microservices gateway.',
    description: 'Engineered a scalable transaction processing engine serving 50k+ transactions per minute with fraud detection.',
    category: 'Enterprise Software',
    technologies: 'Go, Node.js, PostgreSQL, Docker, AWS',
    image_url: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/fintech',
    is_featured: 1
  },
  {
    id: 2,
    title: 'NextGen Logistics & Dispatch System',
    slug: 'nextgen-logistics-dispatch-system',
    summary: 'Real-time automated freight routing, tracking, and vehicle telemetry suite.',
    description: 'Architected an end-to-end logistics platform optimizing driver delivery routes by 28% with live GPS tracking.',
    category: 'Web & Mobile App',
    technologies: 'React, React Native, Node.js, Redis, MongoDB',
    image_url: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/logistics',
    is_featured: 1
  },
  {
    id: 3,
    title: 'AI-Powered Medical Diagnostic Portal',
    slug: 'ai-medical-diagnostic-portal',
    summary: 'Clinical decision support system processing radiographic imaging with computer vision.',
    description: 'Developed a secure HIPAA-compliant portal enabling radiologists to analyze DICOM MRI and CT scans using deep learning.',
    category: 'AI & Healthcare',
    technologies: 'Python, FastAPI, Astro, PyTorch, MySQL',
    image_url: 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/medical',
    is_featured: 1
  },
  {
    id: 4,
    title: 'Omnichannel Luxury E-Commerce Platform',
    slug: 'omnichannel-luxury-ecommerce',
    summary: 'Headless storefront with ultra-fast search, multi-currency checkout, and ERP sync.',
    description: 'Engineered high-converting digital retail experience with 99+ Google Lighthouse score and instant checkout.',
    category: 'Web Development',
    technologies: 'Next.js, TypeScript, Stripe, GraphQL, Tailwind',
    image_url: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/ecommerce',
    is_featured: 1
  },
  {
    id: 5,
    title: 'Industrial IoT Telemetry & Analytics Hub',
    slug: 'industrial-iot-analytics-hub',
    summary: 'Real-time sensor monitoring, predictive equipment maintenance, and energy audits.',
    description: 'Streamed 100k data points/sec with low latency WebSockets and automated anomaly alerting.',
    category: 'Enterprise Software',
    technologies: 'Vue.js, TimescaleDB, MQTT, Go, Docker',
    image_url: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/iot',
    is_featured: 1
  },
  {
    id: 6,
    title: 'Global Travel & Hotel Reservation Engine',
    slug: 'global-travel-reservation-engine',
    summary: 'High-availability booking engine connecting global GDS APIs and live flight schedules.',
    description: 'Built distributed search engine supporting sub-500ms multi-vendor rate comparisons and instantaneous confirmation.',
    category: 'Web & Mobile App',
    technologies: 'Astro, TypeScript, Redis, Cloudflare, Node.js',
    image_url: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=80',
    live_url: 'https://example.com/demo/travel',
    is_featured: 1
  }
];

export const FALLBACK_BLOGS = [
  {
    id: 1,
    title: 'Why We Choose Astro for Content-Driven Software Platforms in 2026',
    slug: 'why-we-choose-astro-in-2026',
    excerpt: 'Discover how Astro Islands architecture delivers instant page loads, zero client-side JavaScript bloat, and superior SEO benchmarks.',
    author: 'Kasun Perera',
    category: 'Web Architecture',
    tags: 'Astro, Performance, Architecture, Jamstack',
    read_time: '5 min read',
    cover_image: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=800&q=80',
    created_at: '2026-09-28'
  },
  {
    id: 2,
    title: 'Building Secure, Scalable REST APIs with Modern PHP 8.4 and MySQL',
    slug: 'building-secure-rest-apis-php-mysql',
    excerpt: 'A practical guide to modern PHP practices, PDO prepared statements, JWT/Session authentication, and clean API design.',
    author: 'NetSpace Tech Team',
    category: 'Backend Engineering',
    tags: 'PHP, MySQL, REST API, Security',
    read_time: '6 min read',
    cover_image: 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
    created_at: '2026-10-01'
  },
  {
    id: 3,
    title: 'High-Performance Redis Caching Strategies for High-Traffic Applications',
    slug: 'redis-caching-strategies-high-traffic',
    excerpt: 'How we achieve sub-10ms response times using Redis in-memory key-value caching and database query invalidation.',
    author: 'NetSpace Engineering',
    category: 'Cloud & DevOps',
    tags: 'Redis, Performance, Caching, Backend',
    read_time: '4 min read',
    cover_image: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=800&q=80',
    created_at: '2026-10-04'
  },
  {
    id: 4,
    title: 'Design Systems in Practice: Crafting Consistent and Accessible Web UIs',
    slug: 'design-systems-in-practice-accessible-uis',
    excerpt: 'Explore design tokens, accessible color contrast standards, and responsive micro-interactions that elevate brand trust.',
    author: 'UI/UX Design Studio',
    category: 'UI/UX Design',
    tags: 'Design System, UI/UX, Accessibility',
    read_time: '5 min read',
    cover_image: 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
    created_at: '2026-10-07'
  },
  {
    id: 5,
    title: 'Deploying Production Web Applications with Zero Downtime via Git CI/CD',
    slug: 'zero-downtime-deployment-git-cicd',
    excerpt: 'Step-by-step walkthrough of automated deployment pipelines, atomic symlink switching, and rollback strategies.',
    author: 'DevOps Team',
    category: 'Infrastructure',
    tags: 'Git, CI/CD, Deployment, Automation',
    read_time: '7 min read',
    cover_image: 'https://images.unsplash.com/photo-1618401471353-b98afee0b2eb?auto=format&fit=crop&w=800&q=80',
    created_at: '2026-10-09'
  }
];

export async function fetchProjects() {
  try {
    const res = await fetch(`${getApiBaseUrl()}/projects.php`);
    if (!res.ok) throw new Error('Network error');
    const json = await res.json();
    return json.data || FALLBACK_PROJECTS;
  } catch (e) {
    return FALLBACK_PROJECTS;
  }
}

export async function fetchBlogs() {
  try {
    const res = await fetch(`${getApiBaseUrl()}/blogs.php`);
    if (!res.ok) throw new Error('Network error');
    const json = await res.json();
    return json.data || FALLBACK_BLOGS;
  } catch (e) {
    return FALLBACK_BLOGS;
  }
}
