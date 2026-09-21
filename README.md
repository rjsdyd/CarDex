🚘 CarDex (글로벌 자동차 데이터 플랫폼)

💡 프로젝트 소개

CarDex는 전 세계 400여 개 제조사의 실시간 자동차 제원을 검색하고 비교할 수 있는 웹 플랫폼입니다. 단순한 데이터 조회를 넘어, 온디맨드 캐싱(On-Demand Caching) 시스템과 직관적인 벤토 박스(Bento-Box) UI를 통해 사용자에게 쾌적하고 최적화된 정보 탐색 경험을 제공하는 것을 목표로 합니다.

진행 기간: 2026.09 ~ 진행 중

상태: 핵심 API 연동 및 UI 개발 완료 (추가 기능 고도화 중)

🚀 주요 기능

스마트 자동차 정보 탐색: 차량 브랜드 및 모델명 기반 검색을 통해 연비(mpg), 구동방식, 연료 타입 등 상세 제원 제공

On-Demand Caching 시스템:

로컬 DB 우선 조회로 응답 속도 극대화 (0.1초)

로컬 DB에 데이터가 없을 경우에만 실시간으로 외부 API(API Ninjas)를 호출하고 DB에 자동 저장

대시보드 실시간 연동: 현재 로컬 DB에 캐싱된 데이터(Row) 개수를 실시간으로 대시보드에 반영

내 차고(찜하기) (개발 예정): 마음에 드는 차량을 하트(❤️) 아이콘으로 찜하여 개인 차고에 저장 및 관리

🛠 기술 스택

Frontend

Core: HTML5, Vanilla JavaScript

UI/UX: CSS3 (CSS Grid 기반 Bento-box 레이아웃)

Backend

Framework: PHP 8.x

Database: MySQL (PDO 기반 안전한 DB 통신)

API: cURL, API Ninjas (Cars API)

Environment: XAMPP (Apache)

🔧 기술적 도전 및 문제 해결

1. 외부 API 의존도 감소 및 응답 속도 최적화 (캐싱 시스템 설계)

문제: 검색마다 외부 API를 호출하면 트래픽 낭비와 1~2초의 로딩 지연이 발생.

해결: 사용자의 검색 요청 시 내 DB(MySQL)를 먼저 조회하는 '온디맨드 캐싱' 아키텍처를 구현. DB에 데이터가 없을 때만 API를 호출하여 데이터를 받아오고 즉시 DB에 저장(Cache Miss 처리)하도록 설계. 두 번째 검색부터는 API 호출 없이 DB에서 0.1초 만에 데이터를 로드(Cache Hit)하여 획기적인 속도 개선.

2. API 통신 보안 정책 및 에러 핸들링 방어 로직

문제: 로컬(XAMPP) 환경에서 cURL을 통한 API 통신 시 SSL 인증서 문제로 통신이 차단되는 현상 및 무료 API 제한(limit 파라미터 등)으로 인해 JSON 배열 대신 에러 문자열이 반환되어 PHP 렌더링 에러가 발생하는 이슈 존재.

해결: CURLOPT_SSL_VERIFYPEER, false 옵션을 통해 로컬 개발 환경의 통신 차단을 해제. 응답 데이터에 error나 message 키가 존재할 경우, 화면이 뻗지 않고 사용자 친화적인 UI로 직관적인 에러 원인을 출력하도록 방어 로직(Error Handling)을 꼼꼼하게 구축.

3. 프론트엔드 관심사의 분리 및 UI/UX 고도화

문제: 하나의 index.php 파일에 백엔드 로직과 방대한 CSS가 혼재되어 코드 가독성 및 유지보수성이 떨어짐.

해결: CSS 코드를 style.css로 완벽히 분리(Separation of Concerns). 추가로, 브라우저의 스타일 캐싱으로 인해 수정 사항이 즉각 반영되지 않는 문제를 해결하기 위해 <link rel="stylesheet" href="style.css?v=<?= time() ?>"> 코드를 적용하여 캐시 버스팅(Cache Busting) 구현. 최신 트렌드인 CSS Grid 기반의 벤토 박스 레이아웃을 도입하여 시각적 만족도 극대화.
