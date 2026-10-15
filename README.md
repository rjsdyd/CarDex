# 🚗 CarDex

> 전 세계 자동차 실시간 제원 검색 및 1:1 비교 데이터 플랫폼

<div align="center">
  <a href="https://php.net/"><img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" /></a>
  <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL" /></a>
  <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript"><img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript" /></a>
</div>

---

## 💡 프로젝트 소개
**CarDex**는 전 세계 다양한 자동차 브랜드의 상세 제원을 실시간으로 검색하고, 1:1 스펙 비교 및 나만의 차고(Wishlist) 기능을 통해 사용자에게 최적의 차량 정보를 제공하는 웹 서비스입니다. 
단순한 정보 나열을 넘어, 외부 API와 로컬 DB를 하이브리드로 연동하여 빠르고 안정적인 대규모 데이터 처리 경험을 제공하는 것을 목표로 합니다.

- **진행 기간:** 2026.09 ~ 2026.10
- **상태:** 개발 완료

## 🚀 주요 기능
- **자동차 정보 탐색:** 로컬 DB와 API Ninjas 하이브리드 연동을 통한 실시간 제원 검색 및 데이터 캐싱
- **제원 비교 및 카테고리 필터링:** 
    * 두 차량의 연비(MPG), 구동 방식, 배기량 등 핵심 스펙 1:1 나란히 비교
    * 제조 국가, 파워트레인, 차급별 다중 조건 필터링 및 리스트 정렬 제공
- **사용자 맞춤형 차고 및 통계:** 
    * 로그인 기반 관심 차량 스크랩(Wishlist) 및 단일/다중 일괄 삭제 기능
    * 저장 데이터를 분석하여 선호 브랜드 및 관심 연료 타입 통계 제공
- **관리자 시스템:** 
    * 관리자(Admin) 전용 대규모 데이터 수집(Seed) 툴 및 가입자 권한 제어 기능

## 🛠 기술 스택
### Frontend
- **Core:** HTML5, Vanilla JavaScript
- **UI/UX:** CSS3, Pretendard Font

### Backend
- **Language:** PHP 8+
- **Database:** MySQL (PDO 연동)
- **API:** API Ninjas (Cars API)

## 🔧 기술적 도전 및 문제 해결
- **API 하이브리드 연동 및 최적화:** 
    * 외부 API(API Ninjas) 호출 전 로컬 DB를 우선 탐색하도록 설계하여 API 호출 한도를 최적화하고 데이터 응답 속도를 대폭 개선.
    * 관리자 전용 대규모 Seed 스크립트 실행 시, 대량의 외부 데이터를 안정적으로 적재하도록 처리.
- **보안 및 데이터 무결성 강화:** 
    * **SQL Injection 방어:** 데이터베이스와 상호작용하는 모든 쿼리에 `PDO::prepare()`와 바인딩 파라미터를 강제 적용.
    * **XSS 방어:** 사용자 입력 및 DB 출력 데이터 렌더링 시 `htmlspecialchars()` 처리를 완벽하게 적용.
    * **세션 보안:** 로그인 성공 시 `session_regenerate_id(true)`를 호출하여 세션 고정 공격을 방지하고, 로그아웃/탈퇴 시 쿠키까지 완전 파기.
- **예외 처리 및 민감 정보 보호:** 
    * 시스템 에러 발생 시 사용자 화면에는 일반적인 오류 안내만 띄우고, 실제 상세 에러 로그는 서버 측에만 은밀히 기록되도록 예외 처리 규격화.
- **UI/UX 고도화:** 
    * 모바일 및 데스크톱 환경 모두에서 깨짐 없이 쾌적하게 동작하는 반응형 웹(Responsive Web) 구현.

---

## 📸 메인 페이지 (검색 및 대시보드)
<div align="center">
  <img width="100%" height="912" alt="image" src="https://github.com/user-attachments/assets/4dba17aa-e0c9-4370-97be-b873247820ec" />
</div>

## 📸 카테고리 탐색
<div align="center">
  <img width="1904" height="909" alt="카테고리" src="[여기에_이미지_링크_삽입]" />
</div>

## 📸 제원 비교 (VS 매치업)
<div align="center">
  <img width="1904" height="909" alt="비교함" src="[여기에_이미지_링크_삽입]" />
</div>

## 📸 상세 페이지
<div align="center">
  <img width="1904" height="909" alt="상세페이지" src="[여기에_이미지_링크_삽입]" />
</div>

## 📸 마이 페이지 (로그인, 회원가입)
<div align="center">
  <img width="1904" height="909" alt="로그인" src="[여기에_이미지_링크_삽입]" /><br/><br/>
  <img width="449" height="547" alt="회원가입" src="[여기에_이미지_링크_삽입]" />
</div>

## 📸 마이 페이지 (유저 차고 및 통계)
<div align="center">
  <img width="1904" height="909" alt="내 차고" src="[여기에_이미지_링크_삽입]" />
</div>

## 📸 관리자 센터 (Admin)
<div align="center">
  <img width="1904" height="909" alt="관리자 센터" src="[여기에_이미지_링크_삽입]" />
</div>
